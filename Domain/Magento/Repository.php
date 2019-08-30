<?php

namespace GetResponse\GetResponseIntegration\Domain\Magento;

use GetResponse\GetResponseIntegration\Domain\GetResponse\CustomFieldsMapping\CustomFieldsMappingCollection;
use GetResponse\GetResponseIntegration\Domain\GetResponse\SubscribeViaRegistration\SubscribeViaRegistration;
use GetResponse\GetResponseIntegration\Helper\Config;
use GrShareCode\Account\Account;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Directory\Model\Country;
use Magento\Framework\App\Cache\Manager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Module\ModuleList;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Repository
 * @package GetResponse\GetResponseIntegration\Domain\Magento
 */
class Repository
{
    /** @var ObjectManagerInterface */
    private $_objectManager;

    /** @var ScopeConfigInterface */
    private $_scopeConfig;

    /** @var WriterInterface */
    private $configWriter;

    /** @var Manager */
    private $cacheManager;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     * @param Manager $cacheManager
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        Manager $cacheManager
    ) {
        $this->_objectManager = $objectManager;
        $this->_scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->cacheManager = $cacheManager;
    }

    /**
     * @return string
     */
    public function getShopId()
    {
        $id = $this->_scopeConfig->getValue(Config::CONFIG_DATA_SHOP_ID);
        return strlen($id) > 0 ? $id : '';
    }

    /**
     * @return string
     */
    public function getShopStatus()
    {
        $status = $this->_scopeConfig->getValue(Config::CONFIG_DATA_SHOP_STATUS);
        return 'enabled' === $status ? 'enabled' : 'disabled';
    }

    /**
     * @return mixed
     */
    public function getCustomers()
    {
        $customers = $this->_objectManager->get('Magento\Customer\Model\Customer');
        return $customers->getCollection()->getData();
    }

    /**
     * @param string $categoryId
     * @return Category
     */
    public function getCategoryById($categoryId)
    {
        return $this->_objectManager
            ->create(Category::class)
            ->load($categoryId);
    }

    /**
     * @return mixed
     */
    public function getFullCustomersDetails()
    {
        $customers = $this->_objectManager->get('Magento\Newsletter\Model\Subscriber');
        $customers = $customers->getCollection();

        $customerEntityTable = $customers->getTable('customer_entity');
        $customerAddressEntityTable = $customers->getTable('customer_address_entity');

        $customers->getSelect()
            ->joinLeft(['customer_entity' => $customerEntityTable], 'customer_entity.entity_id=main_table.customer_id',
                ['*'])
            ->joinLeft(
                ['customer_address_entity' => $customerAddressEntityTable],
                'customer_address_entity.entity_id=default_billing',
                ['*']
            )
            ->where('subscriber_status=1');

        return $customers;
    }

    /**
     * @return array
     */
    public function getAccountInfo()
    {
        return (array)json_decode($this->_scopeConfig->getValue(Config::CONFIG_DATA_ACCOUNT));
    }

    /**
     * @return string
     */
    public function getMagentoCountryCode()
    {
        return $this->_scopeConfig->getValue('general/locale/code');
    }

    /**
     * @return string
     */
    public function getMagentoCurrencyCode()
    {
        $storeManager = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface');
        return $storeManager->getStore()->getCurrentCurrencyCode();
    }

    /**
     * @param string $email
     *
     * @return mixed
     */
    public function loadSubscriberByEmail($email)
    {
        $subscriber = $this->_objectManager
            ->create('Magento\Newsletter\Model\Subscriber')
            ->loadByEmail($email);

        return $subscriber;
    }

    /**
     * @param int $id
     *
     * @return mixed
     */
    public function loadOrder($id)
    {
        $order_object = $this->_objectManager->create('Magento\Sales\Model\Order');
        return $order_object->load($id);
    }

    /**
     * @param int $id
     *
     * @return mixed
     */
    public function loadCustomer($id)
    {
        $customer_object = $this->_objectManager->create('Magento\Customer\Model\Customer');
        return $customer_object->load($id);
    }

    /**
     * @param int $productId
     * @return Product
     */
    public function getProductById($productId)
    {
        $productObject = $this->_objectManager->create(\Magento\Catalog\Model\Product::class);
        return $productObject->load($productId);
    }

    /**
     * @param int $productId
     * @return Product
     */
    public function getProductParentConfigurableById($productId)
    {
        $productObject = $this->_objectManager->create(Configurable::class);
        return $productObject->getParentIdsByChild($productId);
    }

    /**
     * @param int $productId
     * @return Product
     */
    public function getProductConfigurableChildrenById($productId)
    {
        $productObject = $this->_objectManager->create(Configurable::class);
        return $productObject->getChildrenIds($productId);
    }

    /**
     * @param int $id
     *
     * @return mixed
     */
    public function loadCustomerAddress($id)
    {
        $address_object = $this->_objectManager->get('Magento\Customer\Model\Address');

        return $address_object->load($id);
    }

    /**
     * @param ConnectionSettings $settings
     */
    public function saveConnectionSettings(ConnectionSettings $settings)
    {
        $this->configWriter->save(
            Config::CONFIG_DATA_CONNECTION_SETTINGS,
            json_encode($settings->toArray()),
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        );

        $this->cacheManager->clean(['config']);
    }

    /**
     * @return array
     */
    public function getConnectionSettings()
    {
        return (array)json_decode($this->_scopeConfig->getValue(Config::CONFIG_DATA_CONNECTION_SETTINGS));
    }

    /**
     * @param WebEventTrackingSettings $webEventTracking
     */
    public function saveWebEventTracking(WebEventTrackingSettings $webEventTracking)
    {
        $this->configWriter->save(
            Config::CONFIG_DATA_WEB_EVENT_TRACKING,
            json_encode($webEventTracking->toArray()),
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        );

        $this->cacheManager->clean(['config']);
    }

    /**
     * @return array
     */
    public function getWebEventTracking()
    {
        return (array)json_decode($this->_scopeConfig->getValue(Config::CONFIG_DATA_WEB_EVENT_TRACKING));
    }

    /**
     * @param string $status
     */
    public function saveShopStatus($status)
    {
        $this->configWriter->save(
            Config::CONFIG_DATA_SHOP_STATUS,
            $status,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        );

        $this->cacheManager->clean(['config']);
    }

    /**
     * @param string $shopId
     *
     */
    public function saveShopId($shopId)
    {
        $this->configWriter->save(
            Config::CONFIG_DATA_SHOP_ID,
            $shopId,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        );

        $this->cacheManager->clean(['config']);
    }

    /**
     * @param string $listId
     *
     */
    public function saveEcommerceListId($listId)
    {
        $this->configWriter->save(
            Config::CONFIG_DATA_ECOMMERCE_LIST_ID,
            $listId,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        );

        $this->cacheManager->clean(['config']);
    }

    /**
     * @param Account $account
     */
    public function saveAccountDetails(Account $account)
    {
        $this->configWriter->save(
            Config::CONFIG_DATA_ACCOUNT,
            json_encode($this->getAccountAsArray($account)),
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        );

        $this->cacheManager->clean(['config']);
    }

    /**
     * @return array
     */
    public function getRegistrationSettings()
    {
        return (array)json_decode($this->_scopeConfig->getValue(Config::CONFIG_DATA_REGISTRATION_SETTINGS));
    }

    /**
     * @return array
     */
    public function getNewsletterSettings()
    {
        return (array)json_decode($this->_scopeConfig->getValue(Config::CONFIG_DATA_NEWSLETTER_SETTINGS));
    }

    /**
     * @param SubscribeViaRegistration $registrationSettings
     */
    public function saveRegistrationSettings(SubscribeViaRegistration $registrationSettings)
    {
        $this->configWriter->save(
            Config::CONFIG_DATA_REGISTRATION_SETTINGS,
            json_encode($registrationSettings->toArray()),
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        );
        $this->cacheManager->clean(['config']);
    }

    /**
     * @param NewsletterSettings $newsletterSettings
     */
    public function saveNewsletterSettings(NewsletterSettings $newsletterSettings)
    {
        $this->configWriter->save(
            Config::CONFIG_DATA_NEWSLETTER_SETTINGS,
            json_encode($newsletterSettings->toArray()),
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        );
        $this->cacheManager->clean(['config']);
    }

    /**
     * @return array
     */
    public function getCustomFieldsMappingForRegistration()
    {
        return (array)json_decode($this->_scopeConfig->getValue(Config::CONFIG_DATA_REGISTRATION_CUSTOMS), true);
    }

    /**
     * @param array $data
     */
    public function setCustomsOnInit(array $data)
    {
        $this->configWriter->save(
            Config::CONFIG_DATA_REGISTRATION_CUSTOMS,
            json_encode($data),
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        );

        $this->cacheManager->clean(['config']);
    }

    /**
     * @param CustomFieldsMappingCollection $customFieldsMappingCollection
     */
    public function updateCustoms(CustomFieldsMappingCollection $customFieldsMappingCollection)
    {
        $this->configWriter->save(
            Config::CONFIG_DATA_REGISTRATION_CUSTOMS,
            json_encode($customFieldsMappingCollection->toArray()),
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        );

        $this->cacheManager->clean(['config']);
    }

    /**
     * @param WebformSettings $webform
     */
    public function saveWebformSettings(WebformSettings $webform)
    {
        $this->configWriter->save(
            Config::CONFIG_DATA_WEBFORMS_SETTINGS,
            json_encode($webform->toArray()),
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        );

        $this->cacheManager->clean(['config']);
    }

    /**
     * @return array
     */
    public function getWebformSettings()
    {
        return (array)json_decode($this->_scopeConfig->getValue(Config::CONFIG_DATA_WEBFORMS_SETTINGS));
    }

    public function clearDatabase()
    {
        $this->clearConnectionSettings();
        $this->clearRegistrationSettings();
        $this->clearAccountDetails();
        $this->clearWebforms();
        $this->clearWebEventTracking();
        $this->clearCustoms();
        $this->clearEcommerceSettings();
        $this->clearUnauthorizedApiCallDate();
        $this->clearNewsletterSettings();
        $this->clearCustomOrigin();

        $this->cacheManager->clean(['config']);
    }

    private function clearCustomOrigin()
    {
        $this->configWriter->delete(
            Config::CONFIG_DATA_ORIGIN_CUSTOM_FIELD_ID,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        );
    }

    public function clearConnectionSettings()
    {
        $this->configWriter->delete(
            Config::CONFIG_DATA_CONNECTION_SETTINGS,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        );
    }

    public function clearRegistrationSettings()
    {
        $this->configWriter->delete(
            Config::CONFIG_DATA_REGISTRATION_SETTINGS,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        );

        $this->cacheManager->clean(['config']);
    }

    public function clearAccountDetails()
    {
        $this->configWriter->delete(
            Config::CONFIG_DATA_ACCOUNT,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        );
    }

    public function clearWebforms()
    {
        $this->configWriter->delete(
            Config::CONFIG_DATA_WEBFORMS_SETTINGS,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        );
    }

    public function clearNewsletterSettings()
    {
        $this->configWriter->delete(
            Config::CONFIG_DATA_NEWSLETTER_SETTINGS,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        );

        $this->cacheManager->clean(['config']);
    }

    public function clearWebEventTracking()
    {
        $this->configWriter->delete(
            Config::CONFIG_DATA_WEB_EVENT_TRACKING,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        );
    }

    public function clearCustoms()
    {
        $this->configWriter->delete(
            Config::CONFIG_DATA_REGISTRATION_CUSTOMS,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        );
    }

    public function clearEcommerceSettings()
    {
        $this->configWriter->delete(
            Config::CONFIG_DATA_SHOP_STATUS,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        );

        $this->configWriter->delete(
            Config::CONFIG_DATA_SHOP_ID,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        );

        $this->configWriter->delete(
            Config::CONFIG_DATA_ECOMMERCE_LIST_ID,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        );
    }

    /**
     * @param string $customerId
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuotesByCustomerId($customerId)
    {
        return $this->_objectManager
            ->create(\Magento\Quote\Model\Quote::class)
            ->getCollection()
            ->addFieldToFilter('customer_id', (int)$customerId)
            ->setOrder('created_at', 'desc');
    }

    /**
     * @param $quoteId
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuoteById($quoteId)
    {
        return $this->_objectManager
            ->create(\Magento\Quote\Model\Quote::class)
            ->load($quoteId);
    }

    /**
     * @param string $customerId
     * @return Order[]
     */
    public function getOrderByCustomerId($customerId)
    {
        return $this->_objectManager->create(Order::class)
            ->getCollection()
            ->addFieldToFilter('customer_id', (int)$customerId)
            ->setOrder('created_at', 'desc');
    }

    /**
     * @return string
     */
    public function getGetResponsePluginVersion()
    {
        $moduleInfo = $this->_objectManager
            ->create(ModuleList::class)
            ->getOne('GetResponse_GetResponseIntegration');

        return isset($moduleInfo['setup_version']) ? $moduleInfo['setup_version'] : '';
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    public function getStore()
    {
        return $this->_objectManager->create(StoreManagerInterface::class)->getStore();
    }

    /**
     * @param int $countryId
     * @return Country
     */
    public function getCountryCodeByCountryId($countryId)
    {
        return $this->_objectManager
            ->create(Country::class)
            ->load($countryId);
    }

    private function clearUnauthorizedApiCallDate()
    {
        $this->configWriter->delete(
            Config::INVALID_REQUEST_DATE_TIME,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        );
    }

    /**
     * @return string
     */
    public function getEcommerceListId()
    {
        $id = $this->_scopeConfig->getValue(Config::CONFIG_DATA_ECOMMERCE_LIST_ID);
        return strlen($id) > 0 ? $id : '';
    }

    /**
     * @param Account $account
     * @return array
     */
    private function getAccountAsArray(Account $account)
    {
        return [
            'firstName' => $account->getFirstName(),
            'lastName' => $account->getLastName(),
            'email' => $account->getEmail(),
            'phone' => $account->getPhone(),
            'companyName' => $account->getCompanyName(),
            'city' => $account->getCity(),
            'street' => $account->getStreet(),
            'zipCode' => $account->getZipCode()
        ];
    }

}
