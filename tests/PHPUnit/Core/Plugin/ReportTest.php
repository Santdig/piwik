<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

use Piwik\Plugin\Report;
use Piwik\Plugins\ExampleReport\Reports\GetExampleReport;
use Piwik\Plugins\Actions\Columns\ExitPageUrl;
use Piwik\Piwik;
use Piwik\Metrics;
use Piwik\WidgetsList;
use Piwik\Translate;
use Piwik\Menu\MenuReporting;
use Piwik\Plugin\Manager as PluginManager;

class GetBasicReport extends Report
{
    protected function init()
    {
        parent::init();

        $this->name = 'My Custom Report Name';
        $this->order  = 20;
        $this->module = 'TestPlugin';
        $this->action = 'getBasicReport';
        $this->category = 'Goals_Goals';
    }
}

class GetAdvancedReport extends GetBasicReport
{
    protected function init()
    {
        parent::init();

        $this->action      = 'getAdvancedReport';
        $this->widgetTitle = 'Actions_WidgetPageTitlesFollowingSearch';
        $this->menuTitle   = 'Actions_SubmenuPageTitles';
        $this->documentation = Piwik::translate('ExampleReportDocumentation');
        $this->dimension   = new ExitPageUrl();
        $this->metrics     = array('nb_actions', 'nb_visits');
        $this->processedMetrics = array('conversion_rate', 'bounce_rate');
        $this->parameters = array('idGoal' => 1);
        $this->isSubtableReport = true;
        $this->actionToLoadSubTables = 'GetBasicReport';
        $this->constantRowsCount = true;
    }

    public function set($param, $value)
    {
        $this->$param = $value;
    }
}

class GetDisabledReport extends GetBasicReport
{
    public function isEnabled()
    {
        return false;
    }
}

/**
 * @group Core
 */
class Plugin_ReportTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Report
     */
    private $exampleReport;

    /**
     * @var GetDisabledReport
     */
    private $disabledReport;

    /**
     * @var GetBasicReport
     */
    private $basicReport;

    /**
     * @var GetAdvancedReport
     */
    private $advancedReport;

    public function setUp()
    {
        $this->exampleReport  = new GetExampleReport();
        $this->disabledReport = new GetDisabledReport();
        $this->basicReport    = new GetBasicReport();
        $this->advancedReport = new GetAdvancedReport();
    }

    public function tearDown()
    {
        WidgetsList::getInstance()->_reset();
        MenuReporting::getInstance()->unsetInstance();
        Translate::unloadEnglishTranslation();
        parent::tearDown();
    }

    public function test_shouldDetectTheModuleOfTheReportAutomatically()
    {
        $this->assertEquals('ExampleReport', $this->exampleReport->getModule());
    }

    public function test_shouldDetectTheActionOfTheReportAutomatiacally()
    {
        $this->assertEquals('getExampleReport', $this->exampleReport->getAction());
    }

    public function test_getName_shouldReturnTheNameOfTheReport()
    {
        $this->assertEquals('My Custom Report Name', $this->basicReport->getName());
    }

    public function test_isEnabled_shouldBeEnabledByDefault()
    {
        $this->assertTrue($this->basicReport->isEnabled());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage General_ExceptionReportNotEnabled
     */
    public function test_checkIsEnabled_shouldThrowAnExceptionIfReportIsNotEnabled()
    {
        $this->disabledReport->checkIsEnabled();
    }

    public function test_getWidgetTitle_shouldReturnNullIfNoTitleIsSet()
    {
        $this->assertNull($this->basicReport->getWidgetTitle());
    }

    public function test_getWidgetTitle_shouldReturnTranslatedTitleIfSet()
    {
        Translate::loadEnglishTranslation();
        $this->assertEquals('Page Titles Following a Site Search', $this->advancedReport->getWidgetTitle());
    }

    public function test_getCategory_shouldReturnTranslatedCategory()
    {
        Translate::loadEnglishTranslation();
        $this->assertEquals('Goals', $this->advancedReport->getCategory());
    }

    public function test_configureWidget_shouldNotAddAWidgetIfNoWidgetTitleIsSet()
    {
        $widgets = WidgetsList::get();
        $this->assertCount(0, $widgets);

        $this->basicReport->configureWidget(WidgetsList::getInstance());

        $widgets = WidgetsList::get();
        $this->assertCount(0, $widgets);
    }

    public function test_configureWidget_shouldAddAWidgetIfAWidgetTitleIsSet()
    {
        $widgets = WidgetsList::get();
        $this->assertCount(0, $widgets);

        $this->advancedReport->configureWidget(WidgetsList::getInstance());

        $widgets = WidgetsList::get();
        $this->assertCount(1, $widgets);
        $this->assertEquals(array(array(
            'name'       => 'Actions_WidgetPageTitlesFollowingSearch',
            'uniqueId'   => 'widgetTestPlugingetAdvancedReport',
            'parameters' => array('module' => 'TestPlugin', 'action' => 'getAdvancedReport')
        )), $widgets['Goals_Goals']);
    }

    public function test_configureWidget_shouldMixinWidgetParametersIfSet()
    {
        $widgets = WidgetsList::get();
        $this->assertCount(0, $widgets);

        $this->advancedReport->set('widgetParams', array('foo' => 'bar'));
        $this->advancedReport->configureWidget(WidgetsList::getInstance());

        $widgets = WidgetsList::get();
        $this->assertCount(1, $widgets);
        $this->assertEquals(array('module' => 'TestPlugin', 'action' => 'getAdvancedReport', 'foo' => 'bar'),
                            $widgets['Goals_Goals'][0]['parameters']);
    }

    public function test_configureReportingMenu_shouldNotAddAMenuIfNoWidgetTitleIsSet()
    {
        $menu      = MenuReporting::getInstance();
        $menuItems = $menu->getMenu();
        $this->assertNull($menuItems);

        $this->basicReport->configureReportingMenu($menu);

        $menuItems = $menu->getMenu();
        $this->assertNull($menuItems);
    }

    public function test_configureReportingMenu_shouldAddAMenuIfATitleIsSet()
    {
        $menu      = MenuReporting::getInstance();
        $menuItems = $menu->getMenu();
        $this->assertNull($menuItems);

        $this->advancedReport->configureReportingMenu($menu);

        $menuItems = $menu->getMenu();
        
        $expected = array(
            '_tooltip' => '',
            '_order' => 20,
            '_hasSubmenu' => 1,
            'Actions_SubmenuPageTitles' => array(
            '_url' => array(
                'module' => 'TestPlugin',
                'action' => 'menuGetAdvancedReport',
                'idSite' =>  '',
            ),
            '_order' => 20,
            '_name' => 'Actions_SubmenuPageTitles',
            '_tooltip' =>  '',
        ));
        
        $this->assertCount(1, $menuItems);
        $this->assertEquals($expected, $menuItems['Goals_Goals']);
    }

    public function test_getMetrics_shouldUseDefaultMetrics()
    {
        $this->assertEquals(Metrics::getDefaultMetrics(), $this->basicReport->getMetrics());
    }

    public function test_getMetrics_shouldReturnEmptyArray_IfNoMetricsDefined()
    {
        $this->advancedReport->set('metrics', array());
        $this->assertEquals(array(), $this->advancedReport->getMetrics());
    }

    public function test_getMetrics_shouldFindTranslationsForMetricsAndReturnOnlyTheOnesDefinedInSameOrder()
    {
        $expected = array(
            'nb_visits'  => 'General_ColumnNbVisits',
            'nb_actions' => 'General_ColumnNbActions'
        );
        $this->assertEquals($expected, $this->advancedReport->getMetrics());
    }

    public function test_getProcessedMetrics_shouldReturnConfiguredValue_IfNotAnArrayGivenToPreventDefaultMetrics()
    {
        $this->advancedReport->set('processedMetrics', false);
        $this->assertEquals(false, $this->advancedReport->getProcessedMetrics());
    }

    public function test_getProcessedMetrics_shouldReturnEmptyArray_IfNoMetricsDefined()
    {
        $this->advancedReport->set('processedMetrics', array());
        $this->assertEquals(array(), $this->advancedReport->getProcessedMetrics());
    }

    public function test_getProcessedMetrics_reportShouldUseDefaultProcessedMetrics()
    {
        $this->assertEquals(Metrics::getDefaultProcessedMetrics(), $this->basicReport->getProcessedMetrics());
    }

    public function test_getProcessedMetrics_shouldFindTranslationsForMetricsAndReturnOnlyTheOnesDefinedInSameOrder()
    {
        $expected = array(
            'conversion_rate' => 'General_ColumnConversionRate',
            'bounce_rate'     => 'General_ColumnBounceRate'
        );
        $this->assertEquals($expected, $this->advancedReport->getProcessedMetrics());
    }

    public function test_hasGoalMetrics_shouldBeDisabledByDefault()
    {
        $this->assertFalse($this->advancedReport->hasGoalMetrics());
    }

    public function test_hasGoalMetrics_shouldReturnGoalMetricsProperty()
    {
        $this->advancedReport->set('hasGoalMetrics', true);
        $this->assertTrue($this->advancedReport->hasGoalMetrics());
    }

    public function test_configureReportMetadata_shouldNotAddAReportIfReportIsDisabled()
    {
        $reports = array();
        $this->disabledReport->configureReportMetadata($reports, array());
        $this->assertEquals(array(), $reports);
    }

    public function test_configureReportMetadata_shouldAddAReportIfReportIsEnabled()
    {
        $reports = array();
        $this->basicReport->configureReportMetadata($reports, array());
        $this->assertCount(1, $reports);
    }

    public function test_configureReportMetadata_shouldBuiltStructureAndIncludeOnlyFieldsThatAreSet()
    {
        $reports = array();
        $this->basicReport->configureReportMetadata($reports, array());
        $this->assertEquals(array(
            array(
                'category' => 'Goals_Goals',
                'name' => 'My Custom Report Name',
                'module' => 'TestPlugin',
                'action' => 'getBasicReport',
                'metrics' => array(
                    'nb_visits' => 'General_ColumnNbVisits',
                    'nb_uniq_visitors' => 'General_ColumnNbUniqVisitors',
                    'nb_actions' => 'General_ColumnNbActions',
                ),
                'metricsDocumentation' => array(
                    'nb_visits' => 'General_ColumnNbVisitsDocumentation',
                    'nb_uniq_visitors' => 'General_ColumnNbUniqVisitorsDocumentation',
                    'nb_actions' => 'General_ColumnNbActionsDocumentation',
                ),
                'processedMetrics' => array(
                    'nb_actions_per_visit' => 'General_ColumnActionsPerVisit',
                    'avg_time_on_site' => 'General_ColumnAvgTimeOnSite',
                    'bounce_rate' => 'General_ColumnBounceRate',
                    'conversion_rate' => 'General_ColumnConversionRate',
                ),
                'order' => '20'
            )
        ), $reports);
    }

    public function test_configureReportMetadata_shouldBuiltStructureAllFieldsSet()
    {
        $reports = array();
        $this->advancedReport->configureReportMetadata($reports, array());
        $this->assertEquals(array(
            array(
                'category' => 'Goals_Goals',
                'name' => 'My Custom Report Name',
                'module' => 'TestPlugin',
                'action' => 'getAdvancedReport',
                'parameters' => array(
                    'idGoal' => 1
                ),
                'dimension' => 'Actions_ColumnExitPageURL',
                'documentation' => 'ExampleReportDocumentation',
                'isSubtableReport' => true,
                'metrics' => array(
                    'nb_actions' => 'General_ColumnNbActions',
                    'nb_visits' => 'General_ColumnNbVisits'
                ),
                'metricsDocumentation' => array(
                    'nb_actions' => 'General_ColumnNbActionsDocumentation',
                    'nb_visits' => 'General_ColumnNbVisitsDocumentation',
                ),
                'processedMetrics' => array(
                    'conversion_rate' => 'General_ColumnConversionRate',
                    'bounce_rate' => 'General_ColumnBounceRate',
                ),
                'actionToLoadSubTables' => 'GetBasicReport',
                'constantRowsCount' => true,
                'order' => '20'
            )
        ), $reports);
    }

    public function test_factory_shouldNotFindAReportIfReportExistsButPluginIsNotLoaded()
    {
        $this->unloadAllPlugins();

        $report = Report::factory('ExampleReport', 'getExampleReport');

        $this->assertNull($report);
    }

    public function test_factory_shouldFindAReportThatExists()
    {
        $this->loadExampleReportPlugin();

        $module = 'ExampleReport';
        $action = 'getExampleReport';

        $report = Report::factory($module, $action);

        $this->assertInstanceOf('Piwik\Plugins\ExampleReport\Reports\GetExampleReport', $report);
        $this->assertEquals($module, $report->getModule());
        $this->assertEquals($action, $report->getAction());

        // action ucfirst should work as well
        $report = Report::factory($module, ucfirst($action));
        $this->assertInstanceOf('Piwik\Plugins\ExampleReport\Reports\GetExampleReport', $report);
    }

    public function test_factory_shouldNotFindAReportIfPluginLoadedButReportNotExists()
    {
        $this->loadExampleReportPlugin();

        $module = 'ExampleReport';
        $action = 'NotExistingReport';

        $report = Report::factory($module, $action);

        $this->assertNull($report);
    }

    public function test_getAllReports_shouldNotFindAReportIfNoPluginLoaded()
    {
        $this->unloadAllPlugins();

        $report = Report::getAllReports();

        $this->assertEquals(array(), $report);
    }

    public function test_getAllReports_shouldFindAReportIfPluginLoadedButReportNotExists()
    {
        $this->loadExampleReportPlugin();
        $this->loadMorePlugins();

        $reports = Report::getAllReports();

        $this->assertGreaterThan(20, count($reports));

        foreach ($reports as $report) {
            $this->assertInstanceOf('Piwik\Plugin\Report', $report);
        }
    }

    private function loadExampleReportPlugin()
    {
        PluginManager::getInstance()->loadPlugin('ExampleReport');
    }

    private function loadMorePlugins()
    {
        PluginManager::getInstance()->loadPlugin('Actions');
        PluginManager::getInstance()->loadPlugin('DevicesDetection');
        PluginManager::getInstance()->loadPlugin('CoreVisualizations');
        PluginManager::getInstance()->loadPlugin('API');
        PluginManager::getInstance()->loadPlugin('Morpheus');
    }

    private function unloadAllPlugins()
    {
        PluginManager::getInstance()->unloadPlugins();
    }


}