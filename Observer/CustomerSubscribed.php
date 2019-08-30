<?php
namespace GetResponse\GetResponseIntegration\Observer;

use GetResponse\GetResponseIntegration\Domain\GetResponse\Api\ApiException;
use GetResponse\GetResponseIntegration\Domain\GetResponse\Contact\ContactCustomFieldsCollectionFactory;
use GetResponse\GetResponseIntegration\Domain\GetResponse\Contact\ContactService;
use GetResponse\GetResponseIntegration\Domain\GetResponse\SubscribeViaRegistration\SubscribeViaRegistrationService;
use GetResponse\GetResponseIntegration\Domain\Magento\Repository;
use GrShareCode\Api\Exception\GetresponseApiException;
use Magento\Customer\Model\Customer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class CustomerSubscribed
 * @package GetResponse\GetResponseIntegration\Observer
 */
class CustomerSubscribed implements ObserverInterface
{
    /** @var ObjectManagerInterface */
    protected $_objectManager;

    /** @var Repository */
    private $repository;

    /** @var ContactService */
    private $contactService;

    /** @var SubscribeViaRegistrationService */
    private $subscribeViaRegistrationService;

    /** @var ContactCustomFieldsCollectionFactory */
    private $contactCustomFieldsCollectionFactory;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param Repository $repository
     * @param ContactService $contactService
     * @param SubscribeViaRegistrationService $subscribeViaRegistrationService
     * @param ContactCustomFieldsCollectionFactory $contactCustomFieldsCollectionFactory
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Repository $repository,
        ContactService $contactService,
        SubscribeViaRegistrationService $subscribeViaRegistrationService,
        ContactCustomFieldsCollectionFactory $contactCustomFieldsCollectionFactory
    ) {
        $this->_objectManager = $objectManager;
        $this->repository = $repository;
        $this->contactService = $contactService;
        $this->subscribeViaRegistrationService = $subscribeViaRegistrationService;
        $this->contactCustomFieldsCollectionFactory = $contactCustomFieldsCollectionFactory;
    }

    public function execute(Observer $observer)
    {
        try {

            if (!$observer->getEvent()->getSubscriber()->hasDataChanges()) {
                return $this;
            }
            
            $registrationSettings = $this->subscribeViaRegistrationService->getSettings();

            if (!$registrationSettings->isEnabled()) {
                return $this;
            }

            $subscriber = $this->repository->loadSubscriberByEmail($observer->getEvent()->getSubscriber()->getSubscriberEmail());
            $tete = $subscriber->isStatusChanged();
            $test = $observer->getEvent()->getSubscriber()->isStatusChanged();
            
            if (!$subscriber->isSubscribed()) {
                return $this;
            }
            
            $customerData = $this->_objectManager->create('Magento\Customer\Model\Customer');
            $customerData->setWebsiteId($subscriber->getStoreId());
            $customerData->loadByEmail($subscriber->getSubscriberEmail());

            if ($customerData->isEmpty()) {
                return $this;
            }

            /** @var Customer $customer */
            $customer = $this->repository->loadCustomer($customerData->getId());

            $contactCustomFieldsCollection = $this->contactCustomFieldsCollectionFactory->createForCustomer(
                $customer,
                $this->subscribeViaRegistrationService->getCustomFieldMappingSettings(),
                $registrationSettings->isUpdateCustomFieldsEnalbed()
            );

            $this->contactService->addContact(
                $customerData->getEmail(),
                $customerData->getFirstname(),
                $customerData->getLastname(),
                $registrationSettings->getCampaignId(),
                $registrationSettings->getCycleDay(),
                $contactCustomFieldsCollection,
                $registrationSettings->isUpdateCustomFieldsEnalbed()
            );
        } catch (ApiException $e) {
        } catch (GetresponseApiException $e) {
        }

        return $this;
    }
}
