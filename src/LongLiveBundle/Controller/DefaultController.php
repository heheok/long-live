<?php

namespace LongLiveBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function indexAction(Request $request)
    {
        $response = array (
            'status'=>'success',
            'data' => 'System is up.'
        );
        return new JsonResponse($response);
    }

    public function logsAction(Request $request)
    {
        $developerKey = $this->getParameter('developer_key');
        if (!$request->query->get('developerKey') || $request->query->get('developerKey') != $developerKey) {
            $response = array (
                'status'=>'error',
                'data' => 'Invalid Credentials'
            );
        } else {
            $statusService = $this->container->get('longlive.statuscheck');
            $ruleName = $request->query->get('ruleName');

            $hasRule = $statusService->getSingle($ruleName);

            if ($hasRule) {
                $fromDate = \DateTime::createFromFormat('Y-m-d H:i:s', $request->query->get('fromDate'));
                $toDate = \DateTime::createFromFormat('Y-m-d H:i:s', $request->query->get('toDate'));

                $thirtyDaysAgo = date('Y-m-d', strtotime('-30 Days'));
                $today = date('Y-m-d', strtotime('today'));

                if (!$fromDate) {
                    $fromDate = \DateTime::createFromFormat('Y-m-d H:i:s', $thirtyDaysAgo . ' 00:00:00');
                }
                if (!$toDate) {
                    $toDate = \DateTime::createFromFormat('Y-m-d H:i:s', $today . ' 23:59:59');
                }
                $logs = $statusService->getLog($hasRule[0], $fromDate, $toDate);
                $response = array (
                    'status' =>'success',
                    'uptime' =>$logs['uptime'],
                    'currentStatus' =>$logs['isUpNow'],
                    'dayrange' =>$logs['days'],
                    'data' => $logs['result'],
                    'ruleName' => $ruleName,
                );
            } else {
                $response = array (
                    'status'=>'error',
                    'data' => 'You have to supply a valid and existing ruleName'
                );
            }
        }


        return new JsonResponse($response);
    }

    public function dayAction(Request $request) {
        $developerKey = $this->getParameter('developer_key');
        if (!$request->query->get('developerKey') || $request->query->get('developerKey') != $developerKey) {
            $response = array (
                'status'=>'error',
                'data' => 'Invalid Credentials'
            );
        } else {
            $statusService = $this->container->get('longlive.statuscheck');
            $ruleName = $request->query->get('ruleName');
            $day = \DateTime::createFromFormat('Y-m-d H:i:s', $request->query->get('datetime').' 00:00:00');

            $hasRule = $statusService->getSingle($ruleName);

            if ($hasRule && $day) {
                $logs = $statusService->getDay($hasRule[0], $day);
                $response = array (
                    'status' =>'success',
                    'date' => $day->format('Y-m-d'),
                    'ruleName' => $ruleName,
                    'dayAverages' => $logs['totalAverages'],
                    'hourlyAverages' => $logs['hourlyAverages']
                );
            } else {
                $response = array (
                    'status'=>'error',
                    'data' => 'You have to supply a valid and existing ruleName and datetime'
                );
            }
        }
        return new JsonResponse($response);
    }
}


