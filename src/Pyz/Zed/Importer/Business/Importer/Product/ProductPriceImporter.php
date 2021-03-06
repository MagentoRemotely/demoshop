<?php

/**
 * This file is part of the Spryker Demoshop.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\Importer\Business\Importer\Product;

use Exception;
use Orm\Zed\Price\Persistence\SpyPriceProduct;
use Orm\Zed\Price\Persistence\SpyPriceProductQuery;
use Pyz\Zed\Importer\Business\Exception\PriceTypeNotFoundException;
use Pyz\Zed\Importer\Business\Importer\AbstractImporter;
use Spryker\Shared\Library\Collection\Collection;
use Spryker\Zed\Locale\Business\LocaleFacadeInterface;
use Spryker\Zed\Price\Persistence\PriceQueryContainerInterface;
use Spryker\Zed\Product\Persistence\ProductQueryContainerInterface;

class ProductPriceImporter extends AbstractImporter
{

    const ABSTRACT_SKU = 'abstract_sku';
    const PRODUCT_ID = 'product_id';
    const VARIANT_ID = 'variant_id';
    const PRICE = 'price';
    const PRICE_TYPE = 'price_type';

    /**
     * @var \Spryker\Shared\Library\Reader\Csv\CsvReaderInterface
     */
    protected $cvsPriceReader;

    /**
     * @var string
     */
    protected $dataDirectory;

    /**
     * @var \Spryker\Zed\Price\Persistence\PriceQueryContainerInterface
     */
    protected $priceQueryContainer;

    /**
     * @var \Spryker\Zed\Product\Persistence\ProductQueryContainerInterface
     */
    protected $productQueryContainer;

    /**
     * @var \Spryker\Shared\Library\Collection\CollectionInterface
     */
    protected $cachePriceType;

    /**
     * @param \Spryker\Zed\Locale\Business\LocaleFacadeInterface $localeFacade
     * @param \Spryker\Zed\Product\Persistence\ProductQueryContainerInterface $productQueryContainer
     * @param \Spryker\Zed\Price\Persistence\PriceQueryContainerInterface $priceQueryContainer
     */
    public function __construct(
        LocaleFacadeInterface $localeFacade,
        ProductQueryContainerInterface $productQueryContainer,
        PriceQueryContainerInterface $priceQueryContainer
    ) {
        parent::__construct($localeFacade);

        $this->productQueryContainer = $productQueryContainer;
        $this->priceQueryContainer = $priceQueryContainer;

        $this->cachePriceType = new Collection([]);
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return 'Product Price';
    }

    /**
     * @return bool
     */
    public function isImported()
    {
        $query = SpyPriceProductQuery::create();

        return $query->count() > 0;
    }

    /**
     * @param array $data
     *
     * @throws \Exception
     *
     * @return void
     */
    protected function importOne(array $data)
    {
        if (!$data) {
            return;
        }

        $priceType = $this->getPriceTypeEntity($data['price_type']);

        $priceProductEntity = (new SpyPriceProduct())
            ->setPrice($data['price'])
            ->setPriceType($priceType);

        if ($data['abstract_sku']) {
            $productAbstractEntity = $this->getProductAbstractEntity($data['abstract_sku']);

            $priceProductEntity->setFkProductAbstract($productAbstractEntity->getIdProductAbstract());
        } elseif ($data['concrete_sku']) {
            $productConcreteEntity = $this->getProductConcreteEntity($data['concrete_sku']);

            $priceProductEntity->setFkProduct($productConcreteEntity->getIdProduct());
        } else {
            throw new Exception(sprintf('Missing abstract or concrete sku from imported data: %s.', print_r($data, true)));
        }

        $priceProductEntity->save();
    }

    /**
     * @param string $priceType
     *
     * @throws \Pyz\Zed\Importer\Business\Exception\PriceTypeNotFoundException
     *
     * @return string
     */
    protected function getPriceTypeEntity($priceType)
    {
        $priceTypeEntity = null;

        if (!$this->cachePriceType->has($priceType)) {
            $priceTypeEntity = $this->priceQueryContainer
                ->queryPriceType($priceType)
                ->findOne();

            if (!$priceTypeEntity) {
                throw new PriceTypeNotFoundException($priceType);
            }

            $this->cachePriceType->set($priceType, $priceTypeEntity);
        } else {
            $priceTypeEntity = $this->cachePriceType->get($priceType);
        }

        return $priceTypeEntity;
    }

    /**
     * @param string $abstractSku
     *
     * @throws \Exception
     *
     * @return \Orm\Zed\Product\Persistence\SpyProductAbstract
     */
    protected function getProductAbstractEntity($abstractSku)
    {
        $productAbstractEntity = $this->productQueryContainer
            ->queryProductAbstractBySku($abstractSku)
            ->findOne();

        if (!$productAbstractEntity) {
            throw new Exception(sprintf('Invalid abstract product with sku "%s".', $abstractSku));
        }
        return $productAbstractEntity;
    }

    /**
     * @param string $concreteSku
     *
     * @throws \Exception
     *
     * @return \Orm\Zed\Product\Persistence\SpyProduct
     */
    protected function getProductConcreteEntity($concreteSku)
    {
        $productConcreteEntity = $this->productQueryContainer
            ->queryProductConcreteBySku($concreteSku)
            ->findOne();

        if (!$productConcreteEntity) {
            throw new Exception(sprintf('Invalid concrete product with sku "%s".', $concreteSku));
        }

        return $productConcreteEntity;
    }

}
