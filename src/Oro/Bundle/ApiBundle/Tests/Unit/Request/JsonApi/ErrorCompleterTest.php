<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request\JsonApi;

use Oro\Bundle\ApiBundle\Config\Extra\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Exception\NotSupportedConfigOperationException;
use Oro\Bundle\ApiBundle\Filter\FilterNames;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorMetaProperty;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ErrorTitleOverrideProvider;
use Oro\Bundle\ApiBundle\Request\ExceptionTextExtractorInterface;
use Oro\Bundle\ApiBundle\Request\JsonApi\ErrorCompleter;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ErrorCompleterTest extends TestCase
{
    private ExceptionTextExtractorInterface&MockObject $exceptionTextExtractor;
    private ValueNormalizer&MockObject $valueNormalizer;
    private RequestType $requestType;
    private ErrorCompleter $errorCompleter;

    #[\Override]
    protected function setUp(): void
    {
        $this->exceptionTextExtractor = $this->createMock(ExceptionTextExtractorInterface::class);
        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);
        $this->requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $filterNames = $this->createMock(FilterNames::class);
        $filterNames->expects(self::any())
            ->method('getIncludeFilterName')
            ->willReturn('include');
        $filterNames->expects(self::any())
            ->method('getFieldsFilterTemplate')
            ->willReturn('fields[%s]');

        $this->errorCompleter = new ErrorCompleter(
            new ErrorTitleOverrideProvider(['test title alias' => 'test title']),
            $this->exceptionTextExtractor,
            $this->valueNormalizer,
            new FilterNamesRegistry(
                [['filter_names', RequestType::JSON_API]],
                TestContainerBuilder::create()->add('filter_names', $filterNames)->getContainer($this),
                new RequestExpressionMatcher()
            )
        );
    }

    public function testCompleteErrorWhenErrorTitleHasSubstitution(): void
    {
        $error = new Error();
        $error->setStatusCode(400);
        $error->setTitle('test title alias');
        $error->setDetail('test detail');

        $expectedError = new Error();
        $expectedError->setStatusCode(400);
        $expectedError->setTitle('test title');
        $expectedError->setDetail('test detail');

        $this->errorCompleter->complete($error, $this->requestType);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorWithoutInnerException(): void
    {
        $error = new Error();
        $expectedError = new Error();

        $this->errorCompleter->complete($error, $this->requestType);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorWithInnerExceptionAndAlreadyCompletedProperties(): void
    {
        $exception = new \Exception('some exception');

        $error = new Error();
        $error->setStatusCode(400);
        $error->setCode('test code');
        $error->setTitle('test title');
        $error->setDetail('test detail');
        $error->setInnerException($exception);

        $expectedError = new Error();
        $expectedError->setStatusCode(400);
        $expectedError->setCode('test code');
        $expectedError->setTitle('test title');
        $expectedError->setDetail('test detail');
        $expectedError->setInnerException($exception);

        $this->errorCompleter->complete($error, $this->requestType);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorWithInnerExceptionAndExceptionTextExtractorReturnsNothing(): void
    {
        $exception = new \Exception('some exception');

        $error = new Error();
        $error->setInnerException($exception);

        $expectedError = new Error();
        $expectedError->setInnerException($exception);

        $this->errorCompleter->complete($error, $this->requestType);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorWithInnerException(): void
    {
        $exception = new \Exception('some exception');

        $error = new Error();
        $error->setInnerException($exception);

        $expectedError = new Error();
        $expectedError->setStatusCode(500);
        $expectedError->setCode('test code');
        $expectedError->setTitle('test title');
        $expectedError->setDetail('test detail');
        $expectedError->setInnerException($exception);

        $this->exceptionTextExtractor->expects(self::once())
            ->method('getExceptionStatusCode')
            ->with(self::identicalTo($exception))
            ->willReturn($expectedError->getStatusCode());
        $this->exceptionTextExtractor->expects(self::once())
            ->method('getExceptionCode')
            ->with(self::identicalTo($exception))
            ->willReturn($expectedError->getCode());
        $this->exceptionTextExtractor->expects(self::once())
            ->method('getExceptionType')
            ->with(self::identicalTo($exception))
            ->willReturn($expectedError->getTitle());
        $this->exceptionTextExtractor->expects(self::once())
            ->method('getExceptionText')
            ->with(self::identicalTo($exception))
            ->willReturn($expectedError->getDetail());

        $this->errorCompleter->complete($error, $this->requestType);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorTitleByStatusCode(): void
    {
        $error = new Error();
        $error->setStatusCode(400);

        $expectedError = new Error();
        $expectedError->setStatusCode(400);
        $expectedError->setTitle(strtolower(Response::$statusTexts[400]));

        $this->errorCompleter->complete($error, $this->requestType);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorTitleByUnknownStatusCode(): void
    {
        $error = new Error();
        $error->setStatusCode(1000);

        $expectedError = new Error();
        $expectedError->setStatusCode(1000);

        $this->errorCompleter->complete($error, $this->requestType);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorWithPropertyPathAndPointer(): void
    {
        $error = new Error();
        $errorSource = new ErrorSource();
        $errorSource->setPropertyPath('property');
        $errorSource->setPointer('pointer');
        $error->setDetail('test detail');
        $error->setSource($errorSource);

        $expectedError = new Error();
        $expectedErrorSource = new ErrorSource();
        $expectedErrorSource->setPropertyPath('property');
        $expectedErrorSource->setPointer('pointer');
        $expectedError->setDetail('test detail');
        $expectedError->setSource($expectedErrorSource);

        $this->errorCompleter->complete($error, $this->requestType);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorWithPropertyPathAndDetailEndsWithPoint(): void
    {
        $error = new Error();
        $error->setDetail('test detail.');
        $error->setSource(ErrorSource::createByPropertyPath('property'));

        $expectedError = new Error();
        $expectedError->setDetail('test detail. Source: property.');

        $this->errorCompleter->complete($error, $this->requestType);
        self::assertEquals($expectedError, $error);
    }

    /**
     * @dataProvider completeErrorWithPropertyPathButWithoutMetadataDataProvider
     */
    public function testCompleteErrorWithPropertyPathButWithoutMetadata(string $property, array $expectedResult): void
    {
        $error = new Error();
        $error->setDetail('test detail');
        $error->setSource(ErrorSource::createByPropertyPath($property));

        $expectedError = new Error();
        $expectedError->setDetail($expectedResult['detail']);

        $this->errorCompleter->complete($error, $this->requestType);
        self::assertEquals($expectedError, $error);
    }

    public function completeErrorWithPropertyPathButWithoutMetadataDataProvider(): array
    {
        return [
            [
                'id',
                [
                    'detail' => 'test detail. Source: id.'
                ]
            ],
            [
                'firstName',
                [
                    'detail' => 'test detail. Source: firstName.'
                ]
            ],
            [
                'user',
                [
                    'detail' => 'test detail. Source: user.'
                ]
            ],
            [
                'nonMappedPointer',
                [
                    'detail' => 'test detail. Source: nonMappedPointer.'
                ]
            ]
        ];
    }

    public function testCompleteErrorForIdentifier(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $idField = new FieldMetadata();
        $idField->setName('id');
        $metadata->addField($idField);

        $error = new Error();
        $error->setDetail('test detail');
        $error->setSource(ErrorSource::createByPropertyPath('id'));

        $expectedError = new Error();
        $expectedError->setDetail('test detail');
        $expectedError->setSource(ErrorSource::createByPointer('/data/id'));

        $this->errorCompleter->complete($error, $this->requestType, $metadata);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorForField(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $firstNameField = new FieldMetadata();
        $firstNameField->setName('firstName');
        $metadata->addField($firstNameField);

        $error = new Error();
        $error->setDetail('test detail');
        $error->setSource(ErrorSource::createByPropertyPath('firstName'));

        $expectedError = new Error();
        $expectedError->setDetail('test detail');
        $expectedError->setSource(ErrorSource::createByPointer('/data/attributes/firstName'));

        $this->errorCompleter->complete($error, $this->requestType, $metadata);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorForCompositeField(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $firstNameField = new FieldMetadata();
        $firstNameField->setName('field');
        $metadata->addField($firstNameField);

        $error = new Error();
        $error->setDetail('test detail');
        $error->setSource(ErrorSource::createByPropertyPath('field.property'));

        $expectedError = new Error();
        $expectedError->setDetail('test detail');
        $expectedError->setSource(ErrorSource::createByPointer('/data/attributes/field/property'));

        $this->errorCompleter->complete($error, $this->requestType, $metadata);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorForToOneAssociation(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $userAssociation = new AssociationMetadata();
        $userAssociation->setName('user');
        $metadata->addAssociation($userAssociation);

        $error = new Error();
        $error->setDetail('test detail');
        $error->setSource(ErrorSource::createByPropertyPath('user'));

        $expectedError = new Error();
        $expectedError->setDetail('test detail');
        $expectedError->setSource(ErrorSource::createByPointer('/data/relationships/user/data'));

        $this->errorCompleter->complete($error, $this->requestType, $metadata);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorForToManyAssociation(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $groupsAssociation = new AssociationMetadata();
        $groupsAssociation->setName('groups');
        $groupsAssociation->setIsCollection(true);
        $metadata->addAssociation($groupsAssociation);

        $error = new Error();
        $error->setDetail('test detail');
        $error->setSource(ErrorSource::createByPropertyPath('groups'));

        $expectedError = new Error();
        $expectedError->setDetail('test detail');
        $expectedError->setSource(ErrorSource::createByPointer('/data/relationships/groups/data'));

        $this->errorCompleter->complete($error, $this->requestType, $metadata);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorForChildOfToManyAssociation(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $groupsAssociation = new AssociationMetadata();
        $groupsAssociation->setName('groups');
        $groupsAssociation->setIsCollection(true);
        $metadata->addAssociation($groupsAssociation);

        $error = new Error();
        $error->setDetail('test detail');
        $error->setSource(ErrorSource::createByPropertyPath('groups.2'));

        $expectedError = new Error();
        $expectedError->setDetail('test detail');
        $expectedError->setSource(ErrorSource::createByPointer('/data/relationships/groups/data/2'));

        $this->errorCompleter->complete($error, $this->requestType, $metadata);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorForNotMappedPointer(): void
    {
        $error = new Error();
        $error->setDetail('test detail');
        $error->setSource(ErrorSource::createByPropertyPath('notMappedPointer'));

        $expectedError = new Error();
        $expectedError->setDetail('test detail. Source: notMappedPointer.');

        $this->errorCompleter->complete($error, $this->requestType, new EntityMetadata('Test\Entity'));
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorForCollapsedArrayAssociation(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $groupsAssociation = new AssociationMetadata();
        $groupsAssociation->setName('groups');
        $groupsAssociation->setIsCollection(true);
        $groupsAssociation->setDataType('array');
        $groupsAssociation->setCollapsed();
        $metadata->addAssociation($groupsAssociation);

        $error = new Error();
        $error->setDetail('test detail');
        $error->setSource(ErrorSource::createByPropertyPath('groups'));

        $expectedError = new Error();
        $expectedError->setDetail('test detail');
        $expectedError->setSource(ErrorSource::createByPointer('/data/attributes/groups'));

        $this->errorCompleter->complete($error, $this->requestType, $metadata);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorForChildOfCollapsedArrayAssociation(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $groupsAssociation = new AssociationMetadata();
        $groupsAssociation->setName('groups');
        $groupsAssociation->setIsCollection(true);
        $groupsAssociation->setDataType('array');
        $groupsAssociation->setCollapsed();
        $metadata->addAssociation($groupsAssociation);

        $error = new Error();
        $error->setDetail('test detail');
        $error->setSource(ErrorSource::createByPropertyPath('groups.1'));

        $expectedError = new Error();
        $expectedError->setDetail('test detail');
        $expectedError->setSource(ErrorSource::createByPointer('/data/attributes/groups/1'));

        $this->errorCompleter->complete($error, $this->requestType, $metadata);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorForChildFieldOfCollapsedArrayAssociation(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $groupsAssociation = new AssociationMetadata();
        $groupsAssociation->setName('groups');
        $groupsAssociation->setIsCollection(true);
        $groupsAssociation->setDataType('array');
        $groupsAssociation->setCollapsed();
        $metadata->addAssociation($groupsAssociation);

        $error = new Error();
        $error->setDetail('test detail');
        $error->setSource(ErrorSource::createByPropertyPath('groups.1.name'));

        $expectedError = new Error();
        $expectedError->setDetail('test detail');
        $expectedError->setSource(ErrorSource::createByPointer('/data/attributes/groups/1'));

        $this->errorCompleter->complete($error, $this->requestType, $metadata);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorForNotCollapsedArrayAssociation(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $groupsAssociation = new AssociationMetadata();
        $groupsAssociation->setName('groups');
        $groupsAssociation->setIsCollection(true);
        $groupsAssociation->setDataType('array');
        $metadata->addAssociation($groupsAssociation);

        $error = new Error();
        $error->setDetail('test detail');
        $error->setSource(ErrorSource::createByPropertyPath('groups'));

        $expectedError = new Error();
        $expectedError->setDetail('test detail');
        $expectedError->setSource(ErrorSource::createByPointer('/data/attributes/groups'));

        $this->errorCompleter->complete($error, $this->requestType, $metadata);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorForChildOfNotCollapsedArrayAssociation(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $groupsAssociation = new AssociationMetadata();
        $groupsAssociation->setName('groups');
        $groupsAssociation->setIsCollection(true);
        $groupsAssociation->setDataType('array');
        $metadata->addAssociation($groupsAssociation);

        $error = new Error();
        $error->setDetail('test detail');
        $error->setSource(ErrorSource::createByPropertyPath('groups.1'));

        $expectedError = new Error();
        $expectedError->setDetail('test detail');
        $expectedError->setSource(ErrorSource::createByPointer('/data/attributes/groups/1'));

        $this->errorCompleter->complete($error, $this->requestType, $metadata);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorForChildFieldOfNotCollapsedArrayAssociation(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $groupsAssociation = new AssociationMetadata();
        $groupsAssociation->setName('groups');
        $groupsAssociation->setIsCollection(true);
        $groupsAssociation->setDataType('array');
        $metadata->addAssociation($groupsAssociation);

        $error = new Error();
        $error->setDetail('test detail');
        $error->setSource(ErrorSource::createByPropertyPath('groups.1.name'));

        $expectedError = new Error();
        $expectedError->setDetail('test detail');
        $expectedError->setSource(ErrorSource::createByPointer('/data/attributes/groups/1/name'));

        $this->errorCompleter->complete($error, $this->requestType, $metadata);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorForFieldOfEntityWithoutIdentifierFields(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $firstNameField = new FieldMetadata();
        $firstNameField->setName('firstName');
        $metadata->addField($firstNameField);

        $error = new Error();
        $error->setDetail('test detail');
        $error->setSource(ErrorSource::createByPropertyPath('firstName'));

        $expectedError = new Error();
        $expectedError->setDetail('test detail');
        $expectedError->setSource(ErrorSource::createByPointer('/meta/firstName'));

        $this->errorCompleter->complete($error, $this->requestType, $metadata);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorForCompositeFieldOfEntityWithoutIdentifierFields(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $firstNameField = new FieldMetadata();
        $firstNameField->setName('field');
        $metadata->addField($firstNameField);

        $error = new Error();
        $error->setDetail('test detail');
        $error->setSource(ErrorSource::createByPropertyPath('field.property'));

        $expectedError = new Error();
        $expectedError->setDetail('test detail');
        $expectedError->setSource(ErrorSource::createByPointer('/meta/field/property'));

        $this->errorCompleter->complete($error, $this->requestType, $metadata);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorForAssociationOfEntityWithoutIdentifierFields(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $userAssociation = new AssociationMetadata();
        $userAssociation->setName('user');
        $metadata->addAssociation($userAssociation);

        $error = new Error();
        $error->setDetail('test detail');
        $error->setSource(ErrorSource::createByPropertyPath('user'));

        $expectedError = new Error();
        $expectedError->setDetail('test detail');
        $expectedError->setSource(ErrorSource::createByPointer('/meta/user'));

        $this->errorCompleter->complete($error, $this->requestType, $metadata);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorForMetaProperty(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $metadata->addMetaProperty(new MetaPropertyMetadata('test'));

        $error = new Error();
        $error->setDetail('test detail');
        $error->setSource(ErrorSource::createByPropertyPath('test'));

        $expectedError = new Error();
        $expectedError->setDetail('test detail');
        $expectedError->setSource(ErrorSource::createByPointer('/data/meta/test'));

        $this->errorCompleter->complete($error, $this->requestType, $metadata);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorForMetaPropertyOfEntityWithoutIdentifierFields(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->addMetaProperty(new MetaPropertyMetadata('test'));

        $error = new Error();
        $error->setDetail('test detail');
        $error->setSource(ErrorSource::createByPropertyPath('test'));

        $expectedError = new Error();
        $expectedError->setDetail('test detail');
        $expectedError->setSource(ErrorSource::createByPointer('/meta/test'));

        $this->errorCompleter->complete($error, $this->requestType, $metadata);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorForExpandRelatedEntitiesConfigFilterConstraint(): void
    {
        $exception = new NotSupportedConfigOperationException(
            'Test\Class',
            ExpandRelatedEntitiesConfigExtra::NAME
        );

        $error = new Error();
        $error->setInnerException($exception);

        $expectedError = new Error();
        $expectedError->setStatusCode(400);
        $expectedError->setCode('test code');
        $expectedError->setTitle('filter constraint');
        $expectedError->setDetail('The filter is not supported.');
        $expectedError->setSource(ErrorSource::createByParameter('include'));
        $expectedError->setInnerException($exception);

        $this->exceptionTextExtractor->expects(self::once())
            ->method('getExceptionStatusCode')
            ->with(self::identicalTo($exception))
            ->willReturn(400);
        $this->exceptionTextExtractor->expects(self::once())
            ->method('getExceptionCode')
            ->with(self::identicalTo($exception))
            ->willReturn('test code');
        $this->exceptionTextExtractor->expects(self::never())
            ->method('getExceptionType');
        $this->exceptionTextExtractor->expects(self::never())
            ->method('getExceptionText');

        $this->errorCompleter->complete($error, $this->requestType);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorForFilterFieldsConfigFilterConstraint(): void
    {
        $exception = new NotSupportedConfigOperationException(
            'Test\Class',
            FilterFieldsConfigExtra::NAME
        );

        $error = new Error();
        $error->setInnerException($exception);

        $expectedError = new Error();
        $expectedError->setStatusCode(400);
        $expectedError->setCode('test code');
        $expectedError->setTitle('filter constraint');
        $expectedError->setDetail('The filter is not supported.');
        $expectedError->setSource(ErrorSource::createByParameter('fields[test_entity]'));
        $expectedError->setInnerException($exception);

        $this->exceptionTextExtractor->expects(self::once())
            ->method('getExceptionStatusCode')
            ->with(self::identicalTo($exception))
            ->willReturn(400);
        $this->exceptionTextExtractor->expects(self::once())
            ->method('getExceptionCode')
            ->with(self::identicalTo($exception))
            ->willReturn('test code');
        $this->exceptionTextExtractor->expects(self::never())
            ->method('getExceptionType');
        $this->exceptionTextExtractor->expects(self::never())
            ->method('getExceptionText');

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('Test\Class', DataType::ENTITY_TYPE, self::identicalTo($this->requestType))
            ->willReturn('test_entity');

        $this->errorCompleter->complete($error, $this->requestType);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorForIdentifierInCollection(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $idField = new FieldMetadata();
        $idField->setName('id');
        $metadata->addField($idField);

        $error = new Error();
        $error->setDetail('test detail');
        $error->setSource(ErrorSource::createByPropertyPath('1.id'));

        $expectedError = new Error();
        $expectedError->setDetail('test detail');
        $expectedError->setSource(ErrorSource::createByPointer('/data/1/id'));

        $this->errorCompleter->complete($error, $this->requestType, $metadata);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorForFieldInCollection(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $firstNameField = new FieldMetadata();
        $firstNameField->setName('firstName');
        $metadata->addField($firstNameField);

        $error = new Error();
        $error->setDetail('test detail');
        $error->setSource(ErrorSource::createByPropertyPath('1.firstName'));

        $expectedError = new Error();
        $expectedError->setDetail('test detail');
        $expectedError->setSource(ErrorSource::createByPointer('/data/1/attributes/firstName'));

        $this->errorCompleter->complete($error, $this->requestType, $metadata);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorForAssociationInCollection(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $userAssociation = new AssociationMetadata();
        $userAssociation->setName('user');
        $metadata->addAssociation($userAssociation);

        $error = new Error();
        $error->setDetail('test detail');
        $error->setSource(ErrorSource::createByPropertyPath('1.user'));

        $expectedError = new Error();
        $expectedError->setDetail('test detail');
        $expectedError->setSource(ErrorSource::createByPointer('/data/1/relationships/user/data'));

        $this->errorCompleter->complete($error, $this->requestType, $metadata);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorForNotMappedPointerInCollection(): void
    {
        $error = new Error();
        $error->setDetail('test detail');
        $error->setSource(ErrorSource::createByPropertyPath('1.notMappedPointer'));

        $expectedError = new Error();
        $expectedError->setDetail('test detail. Source: 1.notMappedPointer.');

        $this->errorCompleter->complete($error, $this->requestType, new EntityMetadata('Test\Entity'));
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorWithMetaPropertyWithNullValue(): void
    {
        $error = new Error();
        $error->setDetail('test detail');
        $error->addMetaProperty('meta1', new ErrorMetaProperty(null));

        $this->valueNormalizer->expects(self::never())
            ->method('normalizeValue');

        $expectedError = new Error();
        $expectedError->setDetail('test detail');
        $expectedError->addMetaProperty('meta1', new ErrorMetaProperty(null));

        $this->errorCompleter->complete($error, $this->requestType, new EntityMetadata('Test\Entity'));
        self::assertEquals($expectedError, $error);
    }

    /**
     * @dataProvider completeErrorWithMetaPropertiesDataProvider
     */
    public function testCompleteErrorWithMetaProperties(
        string $dataType,
        string $dataTypeForNormalization,
        bool $isArrayAllowed,
        mixed $value,
        mixed $normalizedValue
    ): void {
        $error = new Error();
        $error->setDetail('test detail');
        $error->addMetaProperty('meta1', new ErrorMetaProperty($value, $dataType));

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($value, $dataTypeForNormalization, $this->requestType, $isArrayAllowed)
            ->willReturn($normalizedValue);

        $expectedError = new Error();
        $expectedError->setDetail('test detail');
        $expectedError->addMetaProperty('meta1', new ErrorMetaProperty($normalizedValue, $dataType));

        $this->errorCompleter->complete($error, $this->requestType, new EntityMetadata('Test\Entity'));
        self::assertEquals($expectedError, $error);
    }

    public function completeErrorWithMetaPropertiesDataProvider(): array
    {
        return [
            ['string', 'string', false, 'value', 'normalized_value'],
            ['string[]', 'string', true, ['value'], ['normalized_value']],
            ['integer', 'integer', false, '123', 123],
            ['integer[]', 'integer', true, ['123'], [123]]
        ];
    }

    public function testFixIncludedEntityPathForErrorWithPropertyPathToOwnField(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $association1 = new AssociationMetadata();
        $association1->setName('association1');
        $metadata->addAssociation($association1);

        $error = new Error();
        $error->setDetail('test detail');
        $error->setSource(ErrorSource::createByPropertyPath('association1'));

        $expectedError = new Error();
        $expectedError->setDetail($error->getDetail());
        $expectedError->setSource(
            ErrorSource::createByPointer('/included/0/relationships/association1/data')
        );

        $this->errorCompleter->fixIncludedEntityPath('/included/0', $error, $this->requestType, $metadata);
        self::assertEquals($expectedError, $error);
    }

    public function testFixIncludedEntityPathForErrorWithPropertyPathToNestedField(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $association1 = new AssociationMetadata();
        $association1->setName('association1');
        $metadata->addAssociation($association1);

        $error = new Error();
        $error->setDetail('test detail');
        $error->setSource(ErrorSource::createByPropertyPath('association1.field1'));

        $expectedError = new Error();
        $expectedError->setDetail($error->getDetail());
        $expectedError->setSource(
            ErrorSource::createByPointer('/included/0/relationships/association1/data/field1')
        );

        $this->errorCompleter->fixIncludedEntityPath('/included/0', $error, $this->requestType, $metadata);
        self::assertEquals($expectedError, $error);
    }

    public function testFixIncludedEntityPathForErrorWithPropertyPathToUnknownField(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $error = new Error();
        $error->setDetail('test detail');
        $error->setSource(ErrorSource::createByPropertyPath('association1.field1'));

        $expectedError = new Error();
        $expectedError->setDetail($error->getDetail() . '. Source: association1.field1.');
        $expectedError->setSource(ErrorSource::createByPointer('/included/0'));

        $this->errorCompleter->fixIncludedEntityPath('/included/0', $error, $this->requestType, $metadata);
        self::assertEquals($expectedError, $error);
    }

    public function testFixIncludedEntityPathForErrorWithoutSource(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $association1 = new AssociationMetadata();
        $association1->setName('association1');
        $metadata->addAssociation($association1);

        $error = new Error();
        $error->setDetail('test detail');

        $expectedError = new Error();
        $expectedError->setDetail($error->getDetail());
        $expectedError->setSource(ErrorSource::createByPointer('/included/0'));

        $this->errorCompleter->fixIncludedEntityPath('/included/0', $error, $this->requestType, $metadata);
        self::assertEquals($expectedError, $error);
    }

    public function testFixIncludedEntityPathForErrorWithPointerStartsWithData(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $association1 = new AssociationMetadata();
        $association1->setName('association1');
        $metadata->addAssociation($association1);

        $error = new Error();
        $error->setDetail('test detail');
        $error->setSource(ErrorSource::createByPointer('/data/relationships/association1/data/field1'));

        $expectedError = new Error();
        $expectedError->setDetail($error->getDetail());
        $expectedError->setSource(
            ErrorSource::createByPointer('/included/0/relationships/association1/data/field1')
        );

        $this->errorCompleter->fixIncludedEntityPath('/included/0', $error, $this->requestType, $metadata);
        self::assertEquals($expectedError, $error);
    }
}
