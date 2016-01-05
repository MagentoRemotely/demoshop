<?php

namespace Pyz\Zed\ProductCategory\Business;

use Spryker\Zed\ProductCategory\Business\ProductCategoryManagerInterface;
use Spryker\Zed\ProductCategory\Business\TransferGenerator;
use Spryker\Zed\ProductCategory\Business\ProductCategoryManager;
use Pyz\Zed\ProductCategory\ProductCategoryConfig;
use Spryker\Zed\Library\Import\Reader\CsvFileReader;
use Spryker\Zed\Library\Import\ReaderInterface;
use Psr\Log\LoggerInterface;
use Pyz\Zed\ProductCategory\Business\Internal\DemoData\ProductCategoryMappingInstall;
use Spryker\Zed\ProductCategory\Business\ProductCategoryBusinessFactory as SprykerBusinessFactory;
use Spryker\Zed\ProductCategory\Business\TransferGeneratorInterface;
use Spryker\Zed\ProductCategory\Persistence\ProductCategoryQueryContainer;
use Spryker\Zed\ProductCategory\ProductCategoryDependencyProvider;

/**
 * @method ProductCategoryConfig getConfig()
 * @method ProductCategoryQueryContainer getQueryContainer()
 */
class ProductCategoryBusinessFactory extends SprykerBusinessFactory
{

    /**
     * @param LoggerInterface $messenger
     *
     * @return ProductCategoryMappingInstall
     */
    public function createDemoDataInstaller(LoggerInterface $messenger)
    {
        $installer = new ProductCategoryMappingInstall(
            $this->createProductCategoryManager(),
            $this->createCategoryFacade(),
            $this->createProductFacade(),
            $this->createLocaleFacade(),
            $this->createCSVReader(),
            $this->getConfig()->getDemoDataCSVPath()
        );
        $installer->setMessenger($messenger);

        return $installer;
    }

    /**
     * @return ReaderInterface
     */
    protected function createCSVReader()
    {
        return new CsvFileReader();
    }

    /**
     * @return ProductCategoryManagerInterface
     */
    public function createProductCategoryManager()
    {
        return new ProductCategoryManager(
            $this->createCategoryQueryContainer(),
            $this->getQueryContainer(),
            $this->createProductFacade(),
            $this->createCategoryFacade(),
            $this->createTouchFacade(),
            $this->createCmsFacade(),
            $this->getProvidedDependency(ProductCategoryDependencyProvider::PLUGIN_PROPEL_CONNECTION)
        );
    }

    /**
     * @return TransferGeneratorInterface
     */
    public function createProductCategoryTransferGenerator()
    {
        return new TransferGenerator();
    }

}