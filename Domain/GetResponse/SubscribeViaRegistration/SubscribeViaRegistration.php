<?php
namespace GetResponse\GetResponseIntegration\Domain\GetResponse\SubscribeViaRegistration;

/**
 * Class SubscribeViaRegistration
 * @package GetResponse\GetResponseIntegration\Domain\GetResponse\SubscribeViaRegistration
 */
class SubscribeViaRegistration
{
    /** @var int */
    private $status;

    /** @var int */
    private $customFieldsStatus;

    /** @var null|int */
    private $campaignId;

    /** @var int */
    private $cycleDay;

    /** @var string */
    private $autoresponderId;

    /**
     * @param int $status
     * @param int $customFieldsStatus
     * @param string $campaignId
     * @param null|int $cycleDay
     * @param string $autoresponderId
     */
    public function __construct($status, $customFieldsStatus, $campaignId, $cycleDay, $autoresponderId)
    {
        $this->status = $status;
        $this->customFieldsStatus = $customFieldsStatus;
        $this->campaignId = $campaignId;
        $this->cycleDay = $cycleDay;
        $this->autoresponderId = $autoresponderId;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return 1 === (int)$this->status;
    }

    /**
     * @return string
     */
    public function getAutoresponderId()
    {
        return $this->autoresponderId;
    }

    /**
     * @return bool
     */
    public function isUpdateCustomFieldsEnalbed()
    {
        return 1 === (int)$this->customFieldsStatus;
    }

    /**
     * @return string
     */
    public function getCampaignId()
    {
        return $this->campaignId;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'status' => $this->status,
            'customFieldsStatus' => $this->customFieldsStatus,
            'campaignId' => $this->campaignId,
            'cycleDay' => $this->cycleDay,
            'autoresponderId' => $this->autoresponderId
        ];
    }

    /**
     * @return int
     */
    public function getCycleDay()
    {
        if (strlen($this->cycleDay) > 0) {
            return (int) $this->cycleDay;
        }

        return null;
    }
}
