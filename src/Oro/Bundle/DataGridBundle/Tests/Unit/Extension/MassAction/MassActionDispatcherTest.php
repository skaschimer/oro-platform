<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ArrayDatasource\ArrayDatasource;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResultInterface;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\MassActionInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\DTO\SelectedItems;
use Oro\Bundle\DataGridBundle\Extension\MassAction\IterableResultFactoryRegistry;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHelper;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionParametersParser;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponseInterface;
use Oro\Bundle\FilterBundle\Grid\Extension\OrmFilterExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MassActionDispatcherTest extends TestCase
{
    private const DATAGRID_NAME = 'datagridName';
    private const ACTION_NAME = 'actionName';

    private static array $data = ['some' => 'data'];
    private static array $filters = ['someFilter' => 'data'];

    private MassActionHelper&MockObject $massActionHelper;
    private ManagerInterface&MockObject $manager;
    private MassActionParametersParser&MockObject $massActionParametersParser;
    private IterableResultFactoryRegistry&MockObject $iterableResultFactoryRegistry;
    private MassActionDispatcher $massActionDispatcher;

    #[\Override]
    protected function setUp(): void
    {
        $this->manager = $this->createMock(ManagerInterface::class);
        $this->massActionParametersParser = $this->createMock(MassActionParametersParser::class);
        $this->massActionHelper = $this->createMock(MassActionHelper::class);
        $this->iterableResultFactoryRegistry = $this->createMock(IterableResultFactoryRegistry::class);

        $this->massActionDispatcher = new MassActionDispatcher(
            $this->manager,
            $this->massActionHelper,
            $this->massActionParametersParser,
            $this->iterableResultFactoryRegistry
        );
    }

    public function testDispatchWhenNoItemsSelected(): void
    {
        $parameters = ['inset' => true, 'values' => []];
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('There is nothing to do in mass action "%s"', self::ACTION_NAME));

        $this->massActionDispatcher->dispatch(self::DATAGRID_NAME, self::ACTION_NAME, $parameters, self::$data);
    }

    public function testDispatchByRequestWhenNoItemsSelected(): void
    {
        $request = new Request();
        $this->massActionParametersParser->expects($this->once())
            ->method('parse')
            ->with($request)
            ->willReturn(['inset' => true, 'values' => []]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('There is nothing to do in mass action "%s"', self::ACTION_NAME));

        $this->massActionDispatcher->dispatchByRequest(self::DATAGRID_NAME, self::ACTION_NAME, $request);
    }

    public function testDispatchWhenNoMassActionExtensionAppliedForGrid(): void
    {
        $parameters = ['inset' => true, 'values' => [1], 'filters' => self::$filters];

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Mass action exception');
        $this->setExpectationsForDispatchWhenNoMassAction();

        $this->massActionDispatcher->dispatch(self::DATAGRID_NAME, self::ACTION_NAME, $parameters, self::$data);
    }

    public function testDispatchByRequestWhenNoMassActionExtensionAppliedForGrid(): void
    {
        $request = new Request();
        $this->massActionParametersParser->expects($this->once())
            ->method('parse')
            ->with($request)
            ->willReturn(['inset' => true, 'values' => [1], 'filters' => self::$filters]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Mass action exception');
        $this->setExpectationsForDispatchWhenNoMassAction();

        $this->massActionDispatcher->dispatchByRequest(self::DATAGRID_NAME, self::ACTION_NAME, $request);
    }

    private function createDatagrid(
        ?DatasourceInterface $datasource = null,
        ?DatagridConfiguration $gridConfig = null
    ): DatagridInterface {
        $gridParameters = $this->createMock(ParameterBag::class);
        $gridParameters->expects($this->once())
            ->method('mergeKey')
            ->with(OrmFilterExtension::FILTER_ROOT_PARAM, self::$filters);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->once())
            ->method('getParameters')
            ->willReturn($gridParameters);

        $datagrid->expects($this->any())
            ->method('getAcceptedDatasource')
            ->willReturn($datasource);

        $datagrid->expects($this->any())
            ->method('getConfig')
            ->willReturn($gridConfig);

        $this->manager->expects($this->once())
            ->method('getDatagridByRequestParams')
            ->with(self::DATAGRID_NAME)
            ->willReturn($datagrid);

        return $datagrid;
    }

    private function createMassAction(?ActionConfiguration $actionConfiguration = null): MassActionInterface
    {
        $massAction = $this->createMock(MassActionInterface::class);
        $massAction->expects($this->any())
            ->method('getOptions')
            ->willReturn($actionConfiguration);

        return $massAction;
    }

    private function setExpectationsForDispatchWhenNoMassAction()
    {
        $datagrid = $this->createDatagrid();

        $this->massActionHelper->expects($this->once())
            ->method('getMassActionByName')
            ->with(self::ACTION_NAME, $datagrid)
            ->willThrowException(new LogicException('Mass action exception'));
    }

    public function testDispatchWhenNoHandlerFoundForMassAction(): void
    {
        $parameters = ['inset' => true, 'values' => [1], 'filters' => self::$filters];

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No handler exception');
        $this->setExpectationsForDispatchWhenNoHandlerFoundForMassAction();

        $this->massActionDispatcher->dispatch(self::DATAGRID_NAME, self::ACTION_NAME, $parameters, self::$data);
    }

    public function testDispatchByRequestWhenNoHandlerFoundForMassAction(): void
    {
        $request = new Request();
        $this->massActionParametersParser->expects($this->once())
            ->method('parse')
            ->with($request)
            ->willReturn(['inset' => true, 'values' => [1], 'filters' => self::$filters]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No handler exception');
        $this->setExpectationsForDispatchWhenNoHandlerFoundForMassAction();

        $this->massActionDispatcher->dispatchByRequest(self::DATAGRID_NAME, self::ACTION_NAME, $request);
    }

    private function setExpectationsForDispatchWhenNoHandlerFoundForMassAction()
    {
        $dataGrid = $this->createDatagrid();
        $massAction = $this->createMassAction();

        $this->massActionHelper->expects($this->once())
            ->method('getMassActionByName')
            ->with(self::ACTION_NAME, $dataGrid)
            ->willReturn($massAction);

        $this->massActionHelper->expects($this->once())
            ->method('getHandler')
            ->with($massAction)
            ->willThrowException(new LogicException('No handler exception'));
    }

    public function testDispatchWhenDatasourceIsNotSupported(): void
    {
        $parameters = ['inset' => true, 'values' => [1], 'filters' => self::$filters];

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Not supported datasource');
        $this->setExpectationsForDispatchWhenDatasourceIsNotSupported();

        $this->massActionDispatcher->dispatch(self::DATAGRID_NAME, self::ACTION_NAME, $parameters, self::$data);
    }

    public function testDispatchByRequestWhenDatasourceIsNotSupported(): void
    {
        $request = new Request();
        $this->massActionParametersParser->expects($this->once())
            ->method('parse')
            ->with($request)
            ->willReturn(
                [
                    'inset' => true,
                    'values' => [1],
                    'filters' => self::$filters,
                    MassActionDispatcher::REQUEST_TYPE => Request::METHOD_GET
                ]
            );

        $actionConfiguration = ActionConfiguration::create([]);
        $massAction = $this->createMassAction($actionConfiguration);

        $this->massActionHelper->expects($this->once())
            ->method('isRequestMethodAllowed')
            ->with($massAction, Request::METHOD_GET)
            ->willReturn(true);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Not supported datasource');
        $this->setExpectationsForDispatchWhenDatasourceIsNotSupported();

        $this->massActionDispatcher->dispatchByRequest(self::DATAGRID_NAME, self::ACTION_NAME, $request);
    }

    private function setExpectationsForDispatchWhenDatasourceIsNotSupported()
    {
        $acceptedDatasource = new ArrayDatasource();
        $gridConfig = $this->createMock(DatagridConfiguration::class);
        $dataGrid = $this->createDatagrid($acceptedDatasource, $gridConfig);

        $actionConfiguration = ActionConfiguration::create([]);
        $massAction = $this->createMassAction($actionConfiguration);
        $handler = $this->createMock(MassActionHandlerInterface::class);

        $this->massActionHelper->expects($this->once())
            ->method('getMassActionByName')
            ->with(self::ACTION_NAME, $dataGrid)
            ->willReturn($massAction);

        $this->massActionHelper->expects($this->once())
            ->method('getHandler')
            ->with($massAction)
            ->willReturn($handler);

        $this->iterableResultFactoryRegistry->expects($this->once())
            ->method('createIterableResult')
            ->with($acceptedDatasource, $actionConfiguration, $gridConfig, $this->isInstanceOf(SelectedItems::class))
            ->willThrowException(new LogicException('Not supported datasource'));
    }

    public function testDispatch(): void
    {
        $parameters = ['inset' => true, 'values' => [1], 'filters' => self::$filters];

        $handlerResponse = $this->setExpectationsForDispatch();

        $this->assertSame(
            $handlerResponse,
            $this->massActionDispatcher->dispatch(self::DATAGRID_NAME, self::ACTION_NAME, $parameters, self::$data)
        );
    }

    public function testDispatchByRequest(): void
    {
        self::$data[MassActionDispatcher::REQUEST_TYPE] = Request::METHOD_GET;
        $request = new Request(self::$data);
        $this->massActionParametersParser->expects($this->once())
            ->method('parse')
            ->with($request)
            ->willReturn(
                [
                    'inset' => true,
                    'values' => [1],
                    'filters' => self::$filters,
                    MassActionDispatcher::REQUEST_TYPE => Request::METHOD_GET
                ]
            );

        $actionConfiguration = ActionConfiguration::create([]);
        $massAction = $this->createMassAction($actionConfiguration);

        $this->massActionHelper->expects($this->once())
            ->method('isRequestMethodAllowed')
            ->with($massAction, Request::METHOD_GET)
            ->willReturn(true);

        $handlerResponse = $this->setExpectationsForDispatch();

        $this->assertSame(
            $handlerResponse,
            $this->massActionDispatcher->dispatchByRequest(self::DATAGRID_NAME, self::ACTION_NAME, $request)
        );
    }

    public function testDispatchByRequestWhenHTTPMethodNotAllowed(): void
    {
        self::$data[MassActionDispatcher::REQUEST_TYPE] = Request::METHOD_GET;
        $request = new Request(self::$data);
        $this->massActionParametersParser->expects($this->once())
            ->method('parse')
            ->with($request)
            ->willReturn(['inset' => true, 'values' => [1], 'filters' => self::$filters]);

        $actionConfiguration = ActionConfiguration::create([]);
        $massAction = $this->createMassAction($actionConfiguration);

        $this->massActionHelper->expects($this->once())
            ->method('isRequestMethodAllowed')
            ->with($massAction, Request::METHOD_GET)
            ->willReturn(false);

        $acceptedDatasource = $this->createMock(DatasourceInterface::class);
        $gridConfig = $this->createMock(DatagridConfiguration::class);
        $dataGrid = $this->createDatagrid($acceptedDatasource, $gridConfig);

        $actionConfiguration = ActionConfiguration::create([]);
        $massAction = $this->createMassAction($actionConfiguration);

        $this->massActionHelper->expects($this->once())
            ->method('getMassActionByName')
            ->with(self::ACTION_NAME, $dataGrid)
            ->willReturn($massAction);

        $handler = $this->createMock(MassActionHandlerInterface::class);

        $this->massActionHelper->expects($this->once())
            ->method('getHandler')
            ->with($massAction)
            ->willReturn($handler);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('There is not allowed "GET" HTTP method received.');
        $this->massActionDispatcher->dispatchByRequest(self::DATAGRID_NAME, self::ACTION_NAME, $request);
    }

    private function setExpectationsForDispatch(): MassActionResponseInterface
    {
        $acceptedDatasource = $this->createMock(DatasourceInterface::class);
        $gridConfig = $this->createMock(DatagridConfiguration::class);
        $dataGrid = $this->createDatagrid($acceptedDatasource, $gridConfig);

        $actionConfiguration = ActionConfiguration::create([]);
        $massAction = $this->createMassAction($actionConfiguration);

        $this->massActionHelper->expects($this->once())
            ->method('getMassActionByName')
            ->with(self::ACTION_NAME, $dataGrid)
            ->willReturn($massAction);

        $handler = $this->createMock(MassActionHandlerInterface::class);

        $massActionResponse = $this->createMock(MassActionResponseInterface::class);
        $this->massActionHelper->expects($this->once())
            ->method('getHandler')
            ->with($massAction)
            ->willReturn($handler);

        $iterableResult = $this->createMock(IterableResultInterface::class);

        $handler->expects($this->once())
            ->method('handle')
            ->with(new MassActionHandlerArgs($massAction, $dataGrid, $iterableResult, self::$data))
            ->willReturn($massActionResponse);

        $this->iterableResultFactoryRegistry->expects($this->once())
            ->method('createIterableResult')
            ->with($acceptedDatasource, $actionConfiguration, $gridConfig, $this->isInstanceOf(SelectedItems::class))
            ->willReturn($iterableResult);

        return $massActionResponse;
    }
}
