<?php
namespace GetResponse\GetResponseIntegration\Test\Unit\Domain\GetResponse\CustomFieldsMapping;

use GetResponse\GetResponseIntegration\Domain\GetResponse\CustomFieldsMapping\CustomFieldsMappingService;
use GetResponse\GetResponseIntegration\Domain\GetResponse\CustomFieldsMapping\MagentoCustomerAttribute\MagentoCustomerAttribute;
use GetResponse\GetResponseIntegration\Domain\GetResponse\CustomFieldsMapping\MagentoCustomerAttribute\MagentoCustomerAttributeCollection;
use GetResponse\GetResponseIntegration\Domain\Magento\Repository;
use GetResponse\GetResponseIntegration\Test\BaseTestCase;
use Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory;

class CustomFieldsMappingServiceTest extends BaseTestCase
{

    /** @var Repository|\PHPUnit_Framework_MockObject_MockObject */
    private $repository;

    /** @var CollectionFactory|\PHPUnit_Framework_MockObject_MockObject */
    private $customerAttributeCollectionFactory;

    /** @var CustomFieldsMappingService */
    private $sut;

    protected function setUp()
    {
        $this->repository = $this->getMockWithoutConstructing(Repository::class);
        $this->customerAttributeCollectionFactory = $this->getMockWithoutConstructing(CollectionFactory::class, ['create']);
        $this->sut = new CustomFieldsMappingService($this->repository, $this->customerAttributeCollectionFactory);
    }

    /**
     * @test
     */
    public function shouldSetDefaultCustomFieldsMapping()
    {
        $defaultCustomFieldMappingCollection = [
            [
                'getResponseCustomId' => null,
                'magentoAttributeCode' => 'email',
                'getResponseDefaultLabel' => 'Email',
                'default' => true
            ],
            [
                'getResponseCustomId' => null,
                'magentoAttributeCode' => 'firstname',
                'getResponseDefaultLabel' => 'First Name',
                'default' => true
            ],
            [
                'getResponseCustomId' => null,
                'magentoAttributeCode' => 'lastname',
                'getResponseDefaultLabel' => 'Last Name',
                'default' => true
            ]
        ];

        $this->repository
            ->expects(self::once())
            ->method('setCustomsOnInit')
            ->with($defaultCustomFieldMappingCollection);

        $this->sut->setDefaultCustomFields();
    }

    /**
     * @test
     */
    public function shouldReturnCustomerAttributes()
    {
        $attribute1GenderCode = 'gender';
        $attribute1GenderLabel = 'Gender';
        $attribute2DobCode = 'dob';
        $attribute2DobLabel = 'Date of Birth';

        $customerAttribute1 = $this->getMockWithoutConstructing(\Magento\Customer\Model\Attribute::class);

        $customerAttribute1
            ->expects(self::exactly(2))
            ->method('getAttributeCode')
            ->willReturn($attribute1GenderCode);

        $customerAttribute1
            ->expects(self::exactly(2))
            ->method('__call')
            ->with('getFrontendLabel')
            ->willReturn($attribute1GenderLabel);

        $customerAttribute2 = $this->getMockWithoutConstructing(\Magento\Customer\Model\Attribute::class);

        $customerAttribute2
            ->expects(self::exactly(2))
            ->method('getAttributeCode')
            ->willReturn($attribute2DobCode);

        $customerAttribute2
            ->expects(self::exactly(2))
            ->method('__call')
            ->with('getFrontendLabel')
            ->willReturn($attribute2DobLabel);

        $this->customerAttributeCollectionFactory
            ->expects(self::once())
            ->method('create')
            ->willReturn([$customerAttribute1, $customerAttribute2]);

        $expectedAttributeCollection =  new MagentoCustomerAttributeCollection();
        $expectedAttributeCollection->add(new MagentoCustomerAttribute($attribute1GenderCode, $attribute1GenderLabel));
        $expectedAttributeCollection->add(new MagentoCustomerAttribute($attribute2DobCode, $attribute2DobLabel));

        $this->assertEquals($expectedAttributeCollection, $this->sut->getMagentoCustomerAttributes());
    }

    /**
     * @test
     */
    public function shouldNotReturnCustomerAttributesIfLabelNotFound()
    {
        $customerAttribute1 = $this->getMockWithoutConstructing(\Magento\Customer\Model\Attribute::class);

        $customerAttribute1
            ->expects(self::once())
            ->method('__call')
            ->with('getFrontendLabel')
            ->willReturn(null);

        $customerAttribute2 = $this->getMockWithoutConstructing(\Magento\Customer\Model\Attribute::class);

        $customerAttribute2
            ->expects(self::once())
            ->method('__call')
            ->with('getFrontendLabel')
            ->willReturn(null);

        $this->customerAttributeCollectionFactory
            ->expects(self::once())
            ->method('create')
            ->willReturn([$customerAttribute1, $customerAttribute2]);

        $this->assertEquals(new MagentoCustomerAttributeCollection(), $this->sut->getMagentoCustomerAttributes());
    }

    /**
     * @test
     */
    public function shouldNotReturnCustomerAttributesIfAttributeCodeIsBlacklisted()
    {
        $attribute1Code = 'disable_auto_group_change';
        $attribute1Label = 'Disable Auto Group Change';
        $attribute2Code = 'store_id';
        $attribute2Label = 'StoreId';

        $customerAttribute1 = $this->getMockWithoutConstructing(\Magento\Customer\Model\Attribute::class);

        $customerAttribute1
            ->expects(self::once())
            ->method('getAttributeCode')
            ->willReturn($attribute1Code);

        $customerAttribute1
            ->expects(self::once())
            ->method('__call')
            ->with('getFrontendLabel')
            ->willReturn($attribute1Label);

        $customerAttribute2 = $this->getMockWithoutConstructing(\Magento\Customer\Model\Attribute::class);

        $customerAttribute2
            ->expects(self::once())
            ->method('getAttributeCode')
            ->willReturn($attribute2Code);

        $customerAttribute2
            ->expects(self::once())
            ->method('__call')
            ->with('getFrontendLabel')
            ->willReturn($attribute2Label);

        $this->customerAttributeCollectionFactory
            ->expects(self::once())
            ->method('create')
            ->willReturn([$customerAttribute1, $customerAttribute2]);

        $expectedAttributeCollection =  new MagentoCustomerAttributeCollection();
        $this->assertEquals($expectedAttributeCollection, $this->sut->getMagentoCustomerAttributes());
    }

}
