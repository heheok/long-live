<?php

namespace LongLiveBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CheckResponse
 *
 * @ORM\Table(name="check_response")
 * @ORM\Entity(repositoryClass="LongLiveBundle\Repository\CheckResponseRepository")
 */
class CheckResponse
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="ruleId", type="integer")
     */
    private $ruleId;

    /**
     * @var int
     *
     * @ORM\Column(name="statusCode", type="integer")
     */
    private $statusCode;

    /**
     * @var bool
     *
     * @ORM\Column(name="checkForClue", type="boolean", nullable=true)
     */
    private $checkForClue;

    /**
     * @var bool
     *
     * @ORM\Column(name="clueFound", type="boolean", nullable=true)
     */
    private $clueFound;

    /**
     * @var float
     *
     * @ORM\Column(name="nameLookupTime", type="float", nullable=true)
     */
    private $nameLookupTime;

    /**
     * @var float
     *
     * @ORM\Column(name="connectTime", type="float", nullable=true)
     */
    private $connectTime;

    /**
     * @var float
     *
     * @ORM\Column(name="preTransferTime", type="float", nullable=true)
     */
    private $preTransferTime;

    /**
     * @var float
     *
     * @ORM\Column(name="totalTime", type="float", nullable=true)
     */
    private $totalTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="checkTime", type="datetime")
     */
    private $checkTime;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set ruleId
     *
     * @param integer $ruleId
     *
     * @return CheckResponse
     */
    public function setRuleId($ruleId)
    {
        $this->ruleId = $ruleId;

        return $this;
    }

    /**
     * Get ruleId
     *
     * @return int
     */
    public function getRuleId()
    {
        return $this->ruleId;
    }

    /**
     * Set statusCode
     *
     * @param integer $statusCode
     *
     * @return CheckResponse
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * Get statusCode
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Set checkForClue
     *
     * @param boolean $checkForClue
     *
     * @return CheckResponse
     */
    public function setCheckForClue($checkForClue)
    {
        $this->checkForClue = $checkForClue;

        return $this;
    }

    /**
     * Get checkForClue
     *
     * @return bool
     */
    public function getCheckForClue()
    {
        return $this->checkForClue;
    }

    /**
     * Set clueFound
     *
     * @param boolean $clueFound
     *
     * @return CheckResponse
     */
    public function setClueFound($clueFound)
    {
        $this->clueFound = $clueFound;

        return $this;
    }

    /**
     * Get clueFound
     *
     * @return bool
     */
    public function getClueFound()
    {
        return $this->clueFound;
    }

    /**
     * Set nameLookupTime
     *
     * @param float $nameLookupTime
     *
     * @return CheckResponse
     */
    public function setNameLookupTime($nameLookupTime)
    {
        $this->nameLookupTime = $nameLookupTime;

        return $this;
    }

    /**
     * Get nameLookupTime
     *
     * @return float
     */
    public function getNameLookupTime()
    {
        return $this->nameLookupTime;
    }

    /**
     * Set connectTime
     *
     * @param float $connectTime
     *
     * @return CheckResponse
     */
    public function setConnectTime($connectTime)
    {
        $this->connectTime = $connectTime;

        return $this;
    }

    /**
     * Get connectTime
     *
     * @return float
     */
    public function getConnectTime()
    {
        return $this->connectTime;
    }

    /**
     * Set preTransferTime
     *
     * @param float $preTransferTime
     *
     * @return CheckResponse
     */
    public function setPreTransferTime($preTransferTime)
    {
        $this->preTransferTime = $preTransferTime;

        return $this;
    }

    /**
     * Get preTransferTime
     *
     * @return float
     */
    public function getPreTransferTime()
    {
        return $this->preTransferTime;
    }

    /**
     * Set totalTime
     *
     * @param float $totalTime
     *
     * @return CheckResponse
     */
    public function setTotalTime($totalTime)
    {
        $this->totalTime = $totalTime;

        return $this;
    }

    /**
     * Get totalTime
     *
     * @return float
     */
    public function getTotalTime()
    {
        return $this->totalTime;
    }

    /**
     * Set checkTime
     *
     * @return CheckResponse
     */
    public function setCheckTime($dateStr = false)
    {
        if ($dateStr) {
            $this->checkTime = new \DateTime($dateStr);
        } else {
            $this->checkTime = new \DateTime("now");
        }

        return $this;
    }

    /**
     * Get checkTime
     *
     * @return \DateTime
     */
    public function getCheckTime()
    {
        return $this->checkTime;
    }
}

