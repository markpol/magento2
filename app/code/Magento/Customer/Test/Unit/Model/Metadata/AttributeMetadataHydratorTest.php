<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Customer\Test\Unit\Model\Metadata;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Customer\Api\Data\AttributeMetadataInterface;
use Magento\Customer\Model\Data\AttributeMetadata;
use Magento\Customer\Api\Data\AttributeMetadataInterfaceFactory;
use Magento\Customer\Api\Data\OptionInterface;
use Magento\Customer\Model\Data\Option;
use Magento\Customer\Api\Data\OptionInterfaceFactory;
use Magento\Customer\Api\Data\ValidationRuleInterface;
use Magento\Customer\Model\Data\ValidationRule;
use Magento\Customer\Api\Data\ValidationRuleInterfaceFactory;
use Magento\Customer\Model\Metadata\AttributeMetadataHydrator;
use Magento\Framework\Reflection\DataObjectProcessor;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AttributeMetadataHydratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AttributeMetadataInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $attributeMetadataFactoryMock;

    /**
     * @var OptionInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $optionFactoryMock;

    /**
     * @var ValidationRuleInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $validationRuleFactoryMock;

    /**
     * @var AttributeMetadataInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $attributeMetadataMock;

    /**
     * @var DataObjectProcessor|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dataObjectProcessorMock;

    /**
     * @var AttributeMetadataHydrator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $attributeMetadataHydrator;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->attributeMetadataFactoryMock = $this->getMock(
            AttributeMetadataInterfaceFactory::class,
            ['create'],
            [],
            '',
            false
        );
        $this->optionFactoryMock = $this->getMock(
            OptionInterfaceFactory::class,
            ['create'],
            [],
            '',
            false
        );
        $this->validationRuleFactoryMock = $this->getMock(
            ValidationRuleInterfaceFactory::class,
            ['create'],
            [],
            '',
            false
        );
        $this->attributeMetadataMock = $this->getMock(AttributeMetadataInterface::class);
        $this->dataObjectProcessorMock = $this->getMock(
            DataObjectProcessor::class,
            [],
            [],
            '',
            false
        );
        $this->attributeMetadataHydrator = $objectManager->getObject(
            AttributeMetadataHydrator::class,
            [
                'attributeMetadataFactory' => $this->attributeMetadataFactoryMock,
                'optionFactory' => $this->optionFactoryMock,
                'validationRuleFactory' => $this->validationRuleFactoryMock,
                'dataObjectProcessor' => $this->dataObjectProcessorMock
            ]
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testHydrate()
    {
        $optionOneData = [
            'label' => 'Label 1',
            'options' => null
        ];
        $optionThreeData = [
            'label' => 'Label 3',
            'options' => null
        ];
        $optionFourData = [
            'label' => 'Label 4',
            'options' => null
        ];
        $optionTwoData = [
            'label' => 'Label 2',
            'options' => [$optionThreeData, $optionFourData]
        ];
        $validationRuleOneData = [
            'name' => 'Name 1',
            'value' => 'Value 1'
        ];
        $attributeMetadataData = [
            'attribute_code' => 'attribute_code',
            'frontend_input' => 'hidden',
            'options' => [$optionOneData, $optionTwoData],
            'validation_rules' => [$validationRuleOneData]
        ];

        $optionOne = new Option($optionOneData);
        $this->optionFactoryMock->expects($this->at(0))
            ->method('create')
            ->with(['data' => $optionOneData])
            ->willReturn($optionOne);
        $optionThree = new Option($optionThreeData);
        $this->optionFactoryMock->expects($this->at(1))
            ->method('create')
            ->with(['data' => $optionThreeData])
            ->willReturn($optionThree);
        $optionFour = new Option($optionFourData);
        $this->optionFactoryMock->expects($this->at(2))
            ->method('create')
            ->with(['data' => $optionFourData])
            ->willReturn($optionFour);

        $optionTwoDataPartiallyConverted = [
            'label' => 'Label 2',
            'options' => [$optionThree, $optionFour]
        ];
        $optionFour = new Option($optionTwoDataPartiallyConverted);
        $this->optionFactoryMock->expects($this->at(3))
            ->method('create')
            ->with(['data' => $optionTwoDataPartiallyConverted])
            ->willReturn($optionFour);

        $validationRuleOne = new ValidationRule($validationRuleOneData);
        $this->validationRuleFactoryMock->expects($this->once())
            ->method('create')
            ->with(['data' => $validationRuleOneData])
            ->willReturn($validationRuleOne);

        $attributeMetadataPartiallyConverted = [
            'attribute_code' => 'attribute_code',
            'frontend_input' => 'hidden',
            'options' => [$optionOne, $optionFour],
            'validation_rules' => [$validationRuleOne]
        ];

        $this->attributeMetadataFactoryMock->expects($this->once())
            ->method('create')
            ->with(['data' => $attributeMetadataPartiallyConverted])
            ->willReturn(
                new AttributeMetadata($attributeMetadataPartiallyConverted)
            );

        $attributeMetadata = $this->attributeMetadataHydrator->hydrate($attributeMetadataData);

        $this->assertInstanceOf(AttributeMetadataInterface::class, $attributeMetadata);
        $this->assertEquals(
            $attributeMetadataData['attribute_code'],
            $attributeMetadata->getAttributeCode()
        );
        $this->assertInternalType(
            \PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY,
            $attributeMetadata->getOptions()
        );
        $this->assertArrayHasKey(
            0,
            $attributeMetadata->getOptions()
        );
        $this->assertInstanceOf(OptionInterface::class, $attributeMetadata->getOptions()[0]);
        $this->assertEquals(
            $optionOneData['label'],
            $attributeMetadata->getOptions()[0]->getLabel()
        );
        $this->assertArrayHasKey(1, $attributeMetadata->getOptions());
        $this->assertInstanceOf(OptionInterface::class, $attributeMetadata->getOptions()[1]);

        $this->assertInternalType(
            \PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY,
            $attributeMetadata->getOptions()[1]->getOptions()
        );
        $this->assertArrayHasKey(0, $attributeMetadata->getOptions()[1]->getOptions());
        $this->assertInstanceOf(OptionInterface::class, $attributeMetadata->getOptions()[1]->getOptions()[0]);
        $this->assertEquals(
            $optionThreeData['label'],
            $attributeMetadata->getOptions()[1]->getOptions()[0]->getLabel()
        );
        $this->assertInternalType(
            \PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY,
            $attributeMetadata->getValidationRules()
        );
        $this->assertArrayHasKey(0, $attributeMetadata->getValidationRules());
        $this->assertInstanceOf(ValidationRuleInterface::class, $attributeMetadata->getValidationRules()[0]);
        $this->assertEquals(
            $validationRuleOneData['name'],
            $attributeMetadata->getValidationRules()[0]->getName()
        );
    }

    public function testExtract()
    {
        $data = ['foo' => 'bar'];
        $this->dataObjectProcessorMock->expects($this->once())
            ->method('buildOutputDataArray')
            ->with(
                $this->attributeMetadataMock,
                AttributeMetadataInterface::class
            )
            ->willReturn($data);
        $this->assertSame(
            $data,
            $this->attributeMetadataHydrator->extract($this->attributeMetadataMock)
        );
    }
}
