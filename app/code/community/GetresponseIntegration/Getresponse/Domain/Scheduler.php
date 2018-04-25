<?php

use GetresponseIntegration_Getresponse_Domain_GetresponseException as GetresponseException;

/**
 * Class GetresponseIntegration_Getresponse_Domain_Scheduler
 */
class GetresponseIntegration_Getresponse_Domain_Scheduler
{
    const EXPORT_CART = 'export_cart';
    const REMOVE_CART = 'remove_cart';
    const EXPORT_ORDER = 'export_order';
    const EXPORT_CUSTOMER = 'export_customer';

    static $VALID_TYPES
        = array(
            self::EXPORT_CART,
            self::REMOVE_CART,
            self::EXPORT_ORDER,
            self::EXPORT_CUSTOMER
        );

    /** @var GetresponseIntegration_Getresponse_Model_ScheduleJobsQueue */
    private $jobsQueueModel;

    public function __construct()
    {
        $this->jobsQueueModel = Mage::getModel('getresponse/scheduleJobsQueue');
    }


    /**
     * @param string $customer_id
     * @param string $type
     * @param array  $payload
     *
     * @throws Exception
     */
    public function addToQueue($customer_id, $type, $payload)
    {
        if (!in_array($type, self::$VALID_TYPES)) {
            throw GetresponseException::create_for_invalid_schedule_job_type();
        }

        $this->jobsQueueModel->setData(
            array(
                'customer_id' => $customer_id,
                'type'        => $type,
                'payload'     => json_encode($payload)
            )
        );
        $this->jobsQueueModel->save();
    }

    /**
     * @return GetresponseIntegration_Getresponse_Model_ScheduleJobsQueue[]
     */
    public function getAllJobs()
    {
        $jobsQueueCollection = $this->jobsQueueModel->getCollection();
        return $jobsQueueCollection->getItems();
    }
}
