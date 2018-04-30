<?php
/**
 * Created by PhpStorm.
 * User: harun.akgun
 * Date: 26.02.2016
 * Time: 09:38
 */

namespace LongLiveBundle\Controller;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\TransferStats;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Console\Output\OutputInterface;
use LongLiveBundle\Entity\Rule;
use LongLiveBundle\Entity\CheckResponse;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Question\Question;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\Console\Helper\ProgressBar;


class StatusCheckerController extends Controller{
    public $clue = false;
    public $ruleId = false;
    public $checkResponse = false;

    public function generateFloat($firstMin = 0 ,$firstMax=1, $decimals=2) {
        $result = mt_rand($firstMin,$firstMax);
        if ($decimals > 0) {
            $result.='.';
        }
        for ($i=0;$i<$decimals;$i++) {
            $result.=mt_rand(0,9);
        }
        return (float) $result;
    }

    public function generateData(OutputInterface $output){
        ini_set('memory_limit', '-1');
        $allRules = $this->getAll();
        $em = $this->getDoctrine()->getManager();
        $ruleProgress = new ProgressBar($output, (count($allRules)*30)*(60*24));
        $ruleProgress->start();
        $insertSql = "";
        foreach($allRules as $key=>$ruleDetails){

            $rule = $this->getSingle($ruleDetails[0]);
            $ruleId = $rule[0];
            $statusType = rand(0, 2);

            for($dateDiff = 0;$dateDiff<30;$dateDiff++){
                $date = new \DateTime('-'.$dateDiff.' day');
                $theDay = $date->format('Y-m-d');
                $minutesInDay = 60*24;
                for ($minDiff = 0; $minDiff<$minutesInDay;$minDiff++){
                    if (($minDiff/1000) == (int)($minDiff/1000) && $insertSql != "") {
                        $stmt = $em->getConnection()->prepare($insertSql);
                        $stmt->execute();
                        $stmt->closeCursor();
                        $insertSql = "";
                    }
                    $date = new \DateTime($theDay.' 00:00:00');
                    $newDate = $date->modify('+ '.$minDiff.' minute');
                    $dateToMinute = $newDate->format('Y-m-d H:i:s');

                    $this->checkResponse = new CheckResponse();
                    $status = 200;
                    switch($statusType){
                        case 1:
                            //status 5002 degraded
                            if (rand(0,2)) {
                                $status = 5002;
                            } else {
                                $status = 200;
                            }
                        break;
                        case 2:
                            if (rand(0,1)) {
                                $status = 500;
                            } else {
                                $status = 200;
                            }

                        break;
                    }
                    $this->checkResponse->setStatusCode($status);
                    $sqlParts = $ruleId.','.$status.',NULL,NULL';
                    if ($status != 500) {

                        $nl = $this->generateFloat(0,0,5);
                        $ct = $this->generateFloat(0,0,5);
                        $pt = $this->generateFloat(1,3,5);
                        $tt = $nl + $ct + $pt;
                        $sqlParts .= ','.$nl.','.$ct.','.$pt.','.$tt.',';
                    } else {
                        $sqlParts .= ',NULL,NULL,NULL,NULL,';
                    }
                    $sqlParts .= '"'.$dateToMinute.'"';

                    $insertSql .= 'INSERT INTO check_response (ruleId, statusCode, checkForClue, clueFound, nameLookupTime, connectTime, preTransferTime, totalTime, checkTime) VALUES ('.$sqlParts.');';
                    $ruleProgress->advance();
                }
            }
        }
        $ruleProgress->finish();
    }
    public function getInfo(OutputInterface $output){
        $output->writeln("
            Created and Developed by <info>M. Harun AKGÜN</info>
            Date: <info>25.02.2016</info>
            Time: <info>14:06</info>
            V1.0b
            ");
    }

    public function getList(OutputInterface $output){
        $allRules = $this->getAll();
        $table = new Table($output);
        $table
            ->setHeaders(['Rule Name', 'URL','PORT', 'Clue', 'Timeout'])
            ->setRows($allRules)
        ;
        $table->render();
    }

    public function runAll(OutputInterface $output) {
        $allRules = $this->getAll();
        $results = array();
        foreach($allRules as $key=>$ruleSet) {
            $rule = $this->getSingle($ruleSet[0]);
            $healthy = $this->healthCheck();
            if ($rule && $healthy) {

                $checkResponse = $this->run($rule,$rule[2],$rule[3],$rule[4],$rule[5]);
                $tableRows = array(
                    $ruleSet,
                    $checkResponse->getStatusCode(),
                    $checkResponse->getCheckForClue(),
                    $checkResponse->getClueFound(),
                    $checkResponse->getTotalTime()
                );
                array_push($results,$tableRows);
            }
        }

        $table = new Table($output);
        $table
            ->setHeaders(['RuleName','Status Code', 'Check For Clue','Clue Found?', 'Total Response Time'])
            ->setRows($results);
        $table->render();

    }

    public function runCheck(OutputInterface $output,$ruleName){
        $rule = $this->getSingle($ruleName);
        $healthy = $this->healthCheck();
        if ($rule && $healthy) {
            $checkResponse = $this->run($rule,$rule[2],$rule[3],$rule[4],$rule[5]);
            $tableRows = array(
                $checkResponse->getRuleId(),
                $checkResponse->getStatusCode(),
                $checkResponse->getCheckForClue(),
                $checkResponse->getClueFound(),
                $checkResponse->getTotalTime()
            );
            $table = new Table($output);
            $table
                ->setHeaders(['RuleID','Status Code', 'Check For Clue','Clue Found?', 'Total Response Time'])
                ->setRows([$tableRows]);
            $table->render();
        } else {
            $output->writeln("No such rule Or Network Down Please use <info>-l</info> to see the list of rules.");
        }
    }

    public function removeRule(InputInterface $input,OutputInterface $output,$ruleName,$helper){

        if (!$ruleName) {
            $output->writeln('You have to supply a rulename with this command. Use <info>-l</info> to see all rules.');
        } else {
            $ruleNameCheck = $this->getSingle($ruleName);
            if ($ruleNameCheck){

                $table = new Table($output);
                $table
                    ->setHeaders(['ID','Rule Name', 'URL','PORT', 'Response Should Include','Timeout'])
                    ->setRows([
                        $ruleNameCheck
                    ])
                ;
                $table->render();
                $question = new ConfirmationQuestion("Do you confirm that you want to delete this rule from database? <error>Note: This will also delete all past logs about this rule!</error> (<info>y</info>es/<info>n</info>o) ", false);
                $confirmation = $helper->ask($input, $output, $question);
                if ($confirmation) {
                    $this->removeLog($ruleNameCheck[0]);
                    $this->delete($ruleNameCheck[0]);
                    $output->writeln('Rule and associated logs succesfully removed.');
                } else {
                    $output->writeln('Cancelled by user');
                }
            } else {
                $output->writeln('There is no such rule:<question>'.$ruleName.'</question>. Use <info>-l</info> to see all rules.');
            }
        }
    }

    public function addRule(InputInterface $input,OutputInterface $output,$helper) {

        $question = new Question("<comment>Mandatory</comment> Please enter a name for your rule (alphanumeric) : ", false);
        $ruleName = $helper->ask($input, $output, $question);
        if (!$ruleName)  {
            $output->writeln('<error>Rule name is mandatory.<error>');
            die();
        }
        $question = new Question("<comment>Mandatory</comment> Please enter the URL of the service you want to check : ", false);
        $URL = $helper->ask($input, $output, $question);
        if (!$URL)  {
            $output->writeln('<error>URL is mandatory.</error>');
            die();
        }

        $question = new Question("<comment>Optional</comment> Please enter the Port of the service you want to check (default 80) : ", 80);
        $port = $helper->ask($input, $output, $question);


        $question = new Question("<comment>Optional </comment> Enter a string to check in the response : ", false);
        $clueString = $helper->ask($input, $output, $question);

        $question = new Question("<comment>Optional </comment> Timeout in milliseconds (10000) : ", 10000);
        $timeout = $helper->ask($input, $output, $question);

        $table = new Table($output);
        $table
            ->setHeaders(['Rule Name', 'URL', 'PORT','Response Should Include','Timeout'])
            ->setRows([
                [$ruleName, $URL,$port, $clueString,$timeout],
            ])
        ;
        $table->render();
        $question = new ConfirmationQuestion("Do you confirm that you want to add this rule to database? (<info>y</info>es/<info>n</info>o) ", false);
        $confirmation = $helper->ask($input, $output, $question);

        if ($confirmation) {
            if (!$clueString) $clueString = "";
            if (!$timeout) $timeout = "";
            $definedRules = array (
                'name'      => $ruleName,
                'url'       => $URL,
                'port'      => $port,
                'clue'      => $clueString,
                'timeout'   => $timeout
            );
            $saveResponse = $this->save($definedRules);

            if ($saveResponse) {
                $output->writeln('Rule <info>'.$ruleName.'</info> added succesfully. You can use it now.');
            } else {
                $output->writeln('<error>Cannot save to database.</error>');
            }
        } else {
            $output->writeln('Cancelled by user');
        }

    }

    public function delete($ruleID){
        $criteria = array(
            'id'=>$ruleID
        );
        $singleRecord = $this->getDoctrine()->getRepository('LongLiveBundle:Rule')->findBy($criteria);
        if ($singleRecord) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($singleRecord[0]);
            $em->flush();
            return true;
        } else {
            return false;
        }
    }

    public function getSingle($name){
        $criteria = array(
            'name'=>$name
        );
        $singleRecord = $this->getDoctrine()->getRepository('LongLiveBundle:Rule')->findBy($criteria);

        if ($singleRecord) {
            return array(
                $singleRecord[0]->getId(),
                $singleRecord[0]->getName(),
                $singleRecord[0]->getUrl(),
                $singleRecord[0]->getPort(),
                $singleRecord[0]->getClue(),
                $singleRecord[0]->gettimeout()
            );
        } else {
            return false;
        }
    }

    public function getAll(){
        $response = array();
        $allRecords = $this->getDoctrine()->getRepository('LongLiveBundle:Rule')->findAll();
        foreach($allRecords as $key=>$ruleEntity) {
            $tempArr = array(
                $ruleEntity->getName(),
                $ruleEntity->getUrl(),
                $ruleEntity->getPort(),
                $ruleEntity->getClue(),
                $ruleEntity->getTimeout(),
            );
            array_push($response,$tempArr);

        }
        return $response;
    }

    public function save($ruleParameters) {
        $ruleEntity = new Rule();
        $ruleEntity->setName($ruleParameters['name']);
        $ruleEntity->setUrl($ruleParameters['url']);
        $ruleEntity->setPort($ruleParameters['port']);
        $ruleEntity->setClue($ruleParameters['clue']);
        $ruleEntity->setTimeout($ruleParameters['timeout']);
        $em = $this->getDoctrine()->getManager();
        $em->persist($ruleEntity);
        $em->flush();
        return true;

    }

    public function healthCheck(){
        $alertEmail = $this->getParameter('alert_address');
        $client = new Client();
        $curlSettings = [];
        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')){
            $curlSettings = [
                'curl' => [
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
                ]
            ];
        } 
        

        try {
            $res = $client->request('GET', 'http://www.google.com', $curlSettings);

            if ($res->getStatusCode() == 200) {
                return true;
            } else {
                mail($alertEmail, 'Health Check Failed! (status server cant reach network) - Non 200', date('Y-m-d H:i:s').' Status Code:'.$res->getStatusCode());
                return false;
            }
        } catch (ServerException $exception) {
            mail($alertEmail, 'Health Check Failed! (status server cant reach network) - Server Exception', date('Y-m-d H:i:s').' Status Code:'.$exception->getResponse()->getStatusCode());
            return false;
        } catch (RequestException $exception) {
            if ($exception->getCode()) {
                mail($alertEmail, 'Health Check Failed! (status server cant reach network) - Request Exception With Status Code', date('Y-m-d H:i:s').' Status Code:'.$exception->getCode());
                return false;
            } else {
                $exceptionMessage = $exception->getMessage();
                mail($alertEmail, 'Health Check Failed! (status server cant reach network) - Request Exception Without Status Code', date('Y-m-d H:i:s').' Status Code:'.$exception->getMessage());
                return false;
            }

        }
    }

    public function run($rule,$url,$port,$clue,$timeout,$retry=0) {
        $alertEmail = $this->getParameter('alert_address');
        $maxRetry = 3;
        $client = new Client();
        $statsArray = array();
        $results = array();
        $this->clue = $clue;
        $this->ruleId = $rule[0];
        $this->checkResponse = new CheckResponse();
        $res = "";
        $downReason = "";
        $isDown = false;

        $this->clearOldLogs($this->ruleId);

        $curlSettings = [];
        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')){
            $curlSettings = [
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
            ];
        } 

        try {
            $res = $client->request('GET', $url, [
                'curl'=>$curlSettings,
                'connect_timeout' => ($timeout / 1000),
                'on_stats' => function (TransferStats $stats) {

                    $this->checkResponse->setRuleId($this->ruleId);
                    $this->checkResponse->setCheckTime();
                    $statsArray = $stats->getHandlerStats();
                    if ($stats->hasResponse()) {
                        $statusCode = $stats->getResponse()->getStatusCode();
                        if ($statusCode < 400) {

                            $this->checkResponse->setStatusCode($statusCode);
                            $this->checkResponse->setTotalTime($statsArray['total_time']);
                            $this->checkResponse->setNameLookupTime($statsArray['namelookup_time']);
                            $this->checkResponse->setConnectTime($statsArray['connect_time']);
                            $this->checkResponse->setPreTransferTime($statsArray['pretransfer_time']);
                        } else {

                            $this->checkResponse->setStatusCode($statusCode);
                        }
                    } else {
                        $this->checkResponse->setStatusCode(5001);
                    }
                }
            ]);
        } catch (ServerException $exception) {
            $this->checkResponse->setStatusCode($exception->getResponse()->getStatusCode());
            $downReason = "Remote Server Exception. (generic)";
            $isDown = true;
        } catch (RequestException $exception) {
            if ($exception->getCode()) {
                $this->checkResponse->setStatusCode($exception->getCode());
                $downReason = "Request Exception. (generic)";
                $isDown = true;
            } else {
                $exceptionMessage = $exception->getMessage();
                if (strpos($exceptionMessage,'error 28') !== false) {
                    //Special Timeout HTTP Code
                    $this->checkResponse->setStatusCode(5002);
                    $downReason = "Status checker client timed out.";
                    $isDown = true;
                }

            }

        }

        if ($this->checkResponse->getStatusCode() < 400) {
            if ($this->clue) {
                $this->checkResponse->setCheckForClue(true);
                $body = $res->getBody();
                $position = strpos($body,$this->clue);
                if ($position !== false){
                    $this->checkResponse->setClueFound(true);
                } else {
                    //Special Clue Not Found
                    $this->checkResponse->setClueFound(false);
                    $this->checkResponse->setStatusCode(5003);
                    $downReason = "Requested clue can't be found on remote. Looked for '".$this->clue."'";
                    $isDown = true;
                }
            } else {
                $this->checkResponse->setClueFound(false);
                $this->checkResponse->setCheckForClue(false);
            }
        } else {
            $statusCode = $this->checkResponse->getStatusCode();
            if ($statusCode == "5001") $downReason = "No response from remote.";
            $isDown = true;
        }
        if ($isDown) {

            $alertMessage = $rule[1]." seems to be down. \n";
            $alertMessage .= "Status Code: ".$statusCode."\n";
            $alertMessage .= "Reason: ".$downReason."\n";
            $alertMessage = wordwrap($alertMessage, 70);
            if ($retry == $maxRetry) {
                mail($alertEmail , 'Server Down after '.$retry.' retries', $alertMessage);    
                $em = $this->getDoctrine()->getManager();
                $em->persist($this->checkResponse);
                $em->flush();
                return $this->checkResponse;
            } else {
                mail($alertEmail , 'Server May go Down - Tried for '.$retry.' times', $alertMessage);
                $retry = $retry+1;
                $nextTry =  $this->run($rule,$url,$port,$clue,$timeout,$retry);
                if ($nextTry) {
                    return $nextTry;
                }
            }
            
        } else {
            $em = $this->getDoctrine()->getManager();
            $em->persist($this->checkResponse);
            $em->flush();

            return $this->checkResponse;
        }
    }
    public function clearOldLogs($ruleId){
        $em = $this->getDoctrine()->getManager();
        $sql = sprintf("DELETE FROM check_response WHERE checkTime < DATE_SUB(NOW(), INTERVAL 90 day) AND ruleId = %d", $ruleId);
        $prepareToDelete = $em->getConnection()->prepare($sql);
        $prepareToDelete->execute();
    }
    public function removeLog($ruleId) {
        $em = $this->getDoctrine()->getManager();
        $sql = sprintf("DELETE FROM check_response WHERE ruleId = %d", $ruleId);
        $prepareToDelete = $em->getConnection()->prepare($sql);
        $prepareToDelete->execute();
    }
    public function getDay($ruleId,$datetime){

        $results = array();
        $em = $this->getDoctrine()->getManager();
        $theDate = $datetime->format('Y-m-d');
        //GET AVERAGES OF THE DAY
        $avgSql = "SELECT
                    AVG(nameLookupTime) as NL,
                    AVG(connectTime) as CT,
                    AVG(preTransferTime) as PTT,
                    AVG(totalTime) as TT
                   FROM
                    check_response
                   WHERE
                    DATE(checkTime) = '%s' AND
                    ruleId = %d AND
                    statusCode != 500
                   GROUP BY
                    DATE(checkTime)
                    ";
        $totalAverage =  $em->getConnection()->prepare(sprintf($avgSql,$datetime->format('Y-m-d'),$ruleId));
        $totalAverage->execute();
        $totalAverageResponse = $totalAverage->fetch();
        $totalAverage->closeCursor();
        $hourlyAvgSql = "SELECT
                    statusCode as status,
                    IFNULL(nameLookupTime,0) as NL,
                    IFNULL(connectTime,0) as CT,
                    IFNULL(preTransferTime,0) as PTT,
                    IFNULL(totalTime,0) as TT,
                    DATE_FORMAT(checktime,'%%m-%%d-%%Y %%H:%%i') as time
                   FROM
                    check_response
                   WHERE
                    DATE(checkTime) = '%s' AND
                    ruleId = %d AND
                    statusCode != 500
                   GROUP BY
                    HOUR(checkTime),
                    minute(checkTime)

        ";

        $hourlyAverage = $em->getConnection()->prepare(sprintf($hourlyAvgSql,$datetime->format('Y-m-d'),$ruleId));
        $hourlyAverage->execute();
        $hourlyAverageResponse = $hourlyAverage->fetchAll();
        $hourlyAverage->closeCursor();
        return array(
            'totalAverages' => $totalAverageResponse,
            'hourlyAverages' => $hourlyAverageResponse
        );
    }
    public function getLog($ruleId,$fromDate,$toDate){

        $results = array();
        $em = $this->getDoctrine()->getManager();

        $sql = sprintf("SELECT DATE(checkTime) as date,
                        CASE WHEN statusCode = 500 THEN 'down' ELSE 'degraded' END as status,
                        count(ID) as totalMinutes
                          FROM check_response
                          WHERE
                            ruleId = %d AND
                            statusCode != 200 AND
                            checkTime > '%s' AND
                            checkTime < '%s'
                          GROUP BY ruleId, DATE(checkTime), status
                          ORDER BY ruleId,checkTime ASC",
                        $ruleId,
                        $fromDate->format('Y-m-d H:i:s'),
                        $toDate->format('Y-m-d H:i:s'));

        $problematicStatuses = $em->getConnection()->prepare($sql);
        $problematicStatuses->execute();
        $relatedRecords = $problematicStatuses->fetchAll();
        $problematicStatuses->closeCursor();

        //get time difference from from date to toDate
        $timeDifference = $fromDate->diff($toDate);
        $dateDifference = (int)$timeDifference->format('%a');

        $totalMinutes = $dateDifference * (60*24);
        $downMinutes = 0;
        $degradedMinutes = 0;

        for($i=0;$i<=$dateDifference;$i++) {
            $newDate = new \DateTime($fromDate->format('Y-m-d H:i:s'));
            $newDate = $newDate->add(new \DateInterval('P'.$i.'D'));
            $dateString = $newDate->format('Y-m-d');
            $results[$dateString] = array();
        }

        foreach($relatedRecords as $key=>$record) {
            if (!isset($results[$record['date']])) {
                $results[$record['date']] = array();
            }
            if (!isset($results[$record['date']][$record['status']])) {
                $results[$record['date']][$record['status']] = array();
            }
            $results[$record['date']][$record['status']] = array (
                'total_minutes' => $record['totalMinutes']
            );
            if ($record['status'] == "down") {
                $downMinutes += (int) $record['totalMinutes'];
            }
            if ($record['status'] == "degraded") {
                $degradedMinutes += (int) $record['totalMinutes'];
            }
        }
        $totalDownPercentage = (100 * $downMinutes)/$totalMinutes;
        $totalUptime = number_format((float)(100 - $totalDownPercentage), 3, '.', '');

        $isUpNowSql = sprintf("SELECT CASE WHEN statusCode > 200 THEN 'down' ELSE 'up' END as status
                          FROM check_response
                          WHERE
                            ruleId = %d
                          ORDER BY id desc limit 1",
                        $ruleId);
        $isUpQuery = $em->getConnection()->prepare($isUpNowSql);
        $isUpQuery->execute();
        $upResult = $isUpQuery->fetch();
        $isUpQuery->closeCursor();

        return array (
            'result' => $results,
            'uptime' => $totalUptime,
            'isUpNow' => $upResult['status'],
            'days' => $dateDifference
        );
    }
}


