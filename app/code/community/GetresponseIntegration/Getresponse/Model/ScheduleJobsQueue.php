<?php

/**
 * Class GetresponseIntegration_Getresponse_Model_ScheduleJobsQueue
 */
class GetresponseIntegration_Getresponse_Model_ScheduleJobsQueue extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        parent::_construct();
        $this->_init('getresponse/scheduleJobsQueue');
    }
}