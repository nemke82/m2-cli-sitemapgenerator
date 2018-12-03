<?php
namespace Neman\Generatesitemapxml\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State;

use Magento\Framework\App\Bootstrap;
include('app/bootstrap.php');
$bootstrap = Bootstrap::create(BP, $_SERVER);

$objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
$connection = $resource->getConnection();
$tableName = $resource->getTableName('sitemap'); //gives table name with prefix
 
//Select Data from table
$sql = "Select * FROM " . $tableName;
$result = $connection->fetchAll($sql); // gives associated array, table fields as key in array.

if (!$result)
{
$sql = "Insert Into " . $tableName . " VALUES (1,NULL,'sitemap.xml','/',NULL,1);";
$connection->query($sql);

echo "No sitemap records found. Default (sitemap.xml) added under docroot folder.";
}

else
{
echo "Has something. We will use data from Dashboard --> Marketing --> Sitemap area or sitemap SQL table.";
}

class Generatesitemapxml extends Command
{

    const NAME_ARGUMENT = "name";
    const NAME_OPTION = "option";

    /**
     * Enable/disable configuration
     */
    const XML_PATH_GENERATION_ENABLED = 'sitemap/generate/enabled';

    /**
     * Cronjob expression configuration
     */
    const XML_PATH_CRON_EXPR = 'crontab/default/jobs/generate_sitemaps/schedule/cron_expr';

    /**
     * Error email template configuration
     */
    const XML_PATH_ERROR_TEMPLATE = 'sitemap/generate/error_email_template';

    /**
     * Error email identity configuration
     */
    const XML_PATH_ERROR_IDENTITY = 'sitemap/generate/error_email_identity';

    /**
     * 'Send error emails to' configuration
     */
    const XML_PATH_ERROR_RECIPIENT = 'sitemap/generate/error_email';

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Sitemap\Model\ResourceModel\Sitemap\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Sitemap\Model\ResourceModel\Sitemap\CollectionFactory $collectionFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     */

    public function __construct(State $state,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sitemap\Model\ResourceModel\Sitemap\CollectionFactory $collectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
    ) {
        $state->setAreaCode('adminhtml');
        $this->_scopeConfig = $scopeConfig;
        $this->_collectionFactory = $collectionFactory;
        $this->_storeManager = $storeManager;
        $this->_transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        parent::__construct();
    }


    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {


        $errors = [];

        // check if Marketing --> Sitemap has any fields added
        //$rows = mysql_result(mysql_query('SELECT COUNT(*) FROM sitemap'), 0);


        // check if scheduled generation enabled
        if (!$this->_scopeConfig->isSetFlag(
            self::XML_PATH_GENERATION_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        )
        ) {
            $output->writeln('Sitemap generated at docroot. If Nginx please symlink it to pub/. No scheduled generation enabled still!!!');
        }

        $collection = $this->_collectionFactory->create();
        /* @var $collection \Magento\Sitemap\Model\ResourceModel\Sitemap\Collection */
        foreach ($collection as $sitemap) {
            /* @var $sitemap \Magento\Sitemap\Model\Sitemap */
            try {
                $sitemap->generateXml();
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
                $output->writeln($e->getMessage());
            }
        }

        if ($errors && $this->_scopeConfig->getValue(
                self::XML_PATH_ERROR_RECIPIENT,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
        ) {
            $this->inlineTranslation->suspend();

            $this->_transportBuilder->setTemplateIdentifier(
                $this->_scopeConfig->getValue(
                    self::XML_PATH_ERROR_TEMPLATE,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
            )->setTemplateOptions(
                [
                    'area' => \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE,
                    'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                ]
            )->setTemplateVars(
                ['warnings' => join("\n", $errors)]
            )->setFrom(
                $this->_scopeConfig->getValue(
                    self::XML_PATH_ERROR_IDENTITY,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
            )->addTo(
                $this->_scopeConfig->getValue(
                    self::XML_PATH_ERROR_RECIPIENT,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
            );
            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();

            $this->inlineTranslation->resume();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("sitemap:generate");
        $this->setDescription("Generate xml sitemap");
        $this->setDefinition([
            new InputArgument(self::NAME_ARGUMENT, InputArgument::OPTIONAL, "Name"),
            new InputOption(self::NAME_OPTION, "-a", InputOption::VALUE_NONE, "Option functionality")
        ]);
        parent::configure();
    }
}
?>
