<?php

namespace Backend\Modules\Catalog\Domain\Product;

use Backend\Modules\Catalog\Domain\Brand\Brand;
use Backend\Modules\Catalog\Domain\Cart\CartValue;
use Backend\Modules\Catalog\Domain\Category\Category;
use Backend\Modules\Catalog\Domain\ProductDimension\ProductDimension;
use Backend\Modules\Catalog\Domain\ProductDimensionNotification\ProductDimensionNotification;
use Backend\Modules\Catalog\Domain\ProductOption\ProductOption;
use Backend\Modules\Catalog\Domain\ProductSpecial\ProductSpecial;
use Backend\Modules\Catalog\Domain\SpecificationValue\SpecificationValue;
use Backend\Modules\Catalog\Domain\StockStatus\StockStatus;
use Backend\Modules\Catalog\Domain\Vat\Vat;
use Common\Doctrine\Entity\Meta;
use Backend\Modules\MediaLibrary\Domain\MediaGroup\MediaGroup;
use Common\Locale;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="catalog_products")
 * @ORM\Entity(repositoryClass="ProductRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Product
{
    // Define the sort orders
    const SORT_RANDOM = 'random';
    const SORT_PRICE_ASC = 'price-asc';
    const SORT_PRICE_DESC = 'price-desc';
    const SORT_CREATED_AT = 'create-at';

    // Define product types
    const TYPE_DEFAULT = 1;
    const TYPE_DIMENSIONS = 2;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", name="id")
     */
    public $id;

    /**
     * @var Meta
     *
     * @ORM\ManyToOne(targetEntity="Common\Doctrine\Entity\Meta",cascade={"remove", "persist"})
     * @ORM\JoinColumn(name="meta_id", referencedColumnName="id")
     */
    private $meta;

    /**
     * @var Category
     *
     * @ORM\ManyToOne(targetEntity="Backend\Modules\Catalog\Domain\Category\Category", inversedBy="products")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $category;

    /**
     * @var Brand
     *
     * @ORM\ManyToOne(targetEntity="Backend\Modules\Catalog\Domain\Brand\Brand", inversedBy="products")
     * @ORM\JoinColumn(name="brand_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $brand;

    /**
     * @var Vat
     *
     * @ORM\ManyToOne(targetEntity="Backend\Modules\Catalog\Domain\Vat\Vat")
     * @ORM\JoinColumn(name="vat_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $vat;

    /**
     * @var StockStatus
     *
     * @ORM\ManyToOne(targetEntity="Backend\Modules\Catalog\Domain\StockStatus\StockStatus")
     * @ORM\JoinColumn(name="stock_status_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $stock_status;

    /**
     * @var ProductOption[]
     *
     * @ORM\OneToMany(targetEntity="Backend\Modules\Catalog\Domain\ProductOption\ProductOption", mappedBy="product", cascade={"remove", "persist"})
     * @ORM\OrderBy({"sequence" = "ASC"})
     */
    private $product_options;

    /**
     * @var SpecificationValue[]
     *
     * @ORM\ManyToMany(targetEntity="Backend\Modules\Catalog\Domain\SpecificationValue\SpecificationValue", inversedBy="products", cascade={"persist"})
     * @ORM\JoinTable(
     *     name="catalog_products_specification_values",
     *     joinColumns={@ORM\JoinColumn(name="product_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="specification_value_id", referencedColumnName="id")}
     * )
     * @ORM\OrderBy({"value" = "ASC"})
     */
    private $specification_values;

    /**
     * @var ProductSpecial[]
     *
     * @ORM\OneToMany(targetEntity="Backend\Modules\Catalog\Domain\ProductSpecial\ProductSpecial", mappedBy="product", cascade={"remove", "persist"})
     */
    private $specials;

    /**
     * @var ProductDimension[]
     *
     * @ORM\OneToMany(targetEntity="Backend\Modules\Catalog\Domain\ProductDimension\ProductDimension", mappedBy="product", cascade={"remove", "persist"})
     * @ORM\OrderBy({"width" = "ASC", "height" = "ASC"})
     */
    private $dimensions;

    /**
     * @var ProductDimensionNotification[]
     *
     * @ORM\OneToMany(targetEntity="Backend\Modules\Catalog\Domain\ProductDimensionNotification\ProductDimensionNotification", mappedBy="product", cascade={"remove", "persist"})
     * @ORM\OrderBy({"width" = "ASC", "height" = "ASC"})
     */
    private $dimension_notifications;

    /**
     * Many Products may have many related products.
     * @var Product[]
     *
     * @ORM\ManyToMany(targetEntity="Product")
     * @ORM\JoinTable(name="catalog_related_products",
     *     joinColumns={@ORM\JoinColumn(name="product_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="related_product_id", referencedColumnName="id")}
     * )
     * @ORM\OrderBy({"sequence" = "ASC"})
     */
    private $related_products;

    /**
     * @ORM\OneToMany(targetEntity="Backend\Modules\Catalog\Domain\UpSellProduct\UpSellProduct", mappedBy="product", cascade={"persist", "remove"})
     * @ORM\OrderBy({"sequence" = "ASC"})
     */
    protected $up_sell_products;

    /**
     * @var CartValue[]
     *
     * @ORM\OneToMany(targetEntity="Backend\Modules\Catalog\Domain\Cart\CartValue", mappedBy="product", cascade={"remove", "persist"})
     */
    private $cart_values;

    /**
     * @var Locale
     *
     * @ORM\Column(type="locale", name="language")
     */
    private $locale;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default" : false})
     */
    private $hidden;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"default" : 1})
     */
    private $type;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"default" : 0})
     */
    private $min_width;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"default" : 0})
     */
    private $min_height;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"default" : 0})
     */
    private $max_width;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"default" : 0})
     */
    private $max_height;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"default" : 0})
     */
    private $extra_production_width;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"default" : 0})
     */
    private $extra_production_height;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $sku;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ean13;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $isbn;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $weight;

    /**
     * @var float
     *
     * @ORM\Column(type="decimal", precision=10, scale=2)
     */
    private $price;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $stock;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $order_quantity;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    private $from_stock;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $summary;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $text;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $dimension_instructions;

    /**
     * @var MediaGroup
     *
     * @ORM\OneToOne(
     *      targetEntity="Backend\Modules\MediaLibrary\Domain\MediaGroup\MediaGroup",
     *      cascade="persist",
     *      orphanRemoval=true
     * )
     * @ORM\JoinColumn(
     *      name="imageGroupId",
     *      referencedColumnName="id",
     *      onDelete="cascade"
     * )
     * @ORM\OrderBy({"sequence" = "ASC"})
     */
    protected $images;

    /**
     * @var MediaGroup
     *
     * @ORM\OneToOne(
     *      targetEntity="Backend\Modules\MediaLibrary\Domain\MediaGroup\MediaGroup",
     *      cascade="persist",
     *      orphanRemoval=true
     * )
     * @ORM\JoinColumn(
     *      name="downloadGroupId",
     *      referencedColumnName="id",
     *      onDelete="cascade"
     * )
     * @ORM\OrderBy({"sequence" = "ASC"})
     */
    protected $downloads;

    /**
     * @ORM\Column(type="integer", length=11, nullable=true)
     */
    private $sequence;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", name="created_on")
     */
    private $createdOn;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", name="edited_on")
     */
    private $editedOn;

    /**
     * Current object active price
     *
     * @var float
     */
    private $activePrice;

    /**
     * @var boolean
     */
    private $hasActiveSpecialPrice = false;

    private function __construct(
        Meta $meta,
        Category $category,
        ?Brand $brand,
        Vat $vat,
        StockStatus $stock_status,
        Locale $locale,
        bool $hidden,
        int $type,
        int $min_width,
        int $min_height,
        int $max_width,
        int $max_height,
        int $extra_production_width,
        int $extra_production_height,
        string $title,
        float $weight,
        float $price,
        int $stock,
        int $order_quantity,
        bool $from_stock,
        string $sku,
        ?string $ean13,
        ?string $isbn,
        string $summary,
        ?string $text,
        ?string $dimension_instructions,
        int $sequence,
        MediaGroup $images,
        MediaGroup $downloads,
        $specification_values,
        $specials,
        $related_products,
        $up_sell_products,
        $dimensions,
        $dimension_notifications
    )
    {
        $this->cart_values = new ArrayCollection();
        $this->meta = $meta;
        $this->category = $category;
        $this->brand = $brand;
        $this->vat = $vat;
        $this->stock_status = $stock_status;
        $this->locale = $locale;
        $this->hidden = $hidden;
        $this->type = $type;
        $this->dimension_instructions = $dimension_instructions;
        $this->min_width = $min_width;
        $this->min_height = $min_height;
        $this->max_width = $max_width;
        $this->max_height = $max_height;
        $this->extra_production_width = $extra_production_width;
        $this->extra_production_height = $extra_production_height;
        $this->type = $type;
        $this->title = $title;
        $this->weight = $weight;
        $this->price = $price;
        $this->stock = $stock;
        $this->order_quantity = $order_quantity;
        $this->from_stock = $from_stock;
        $this->sku = $sku;
        $this->ean13 = $ean13;
        $this->isbn = $isbn;
        $this->summary = $summary;
        $this->text = $text;
        $this->sequence = $sequence;
        $this->images = $images;
        $this->downloads = $downloads;
        $this->specification_values = $specification_values;
        $this->specials = $specials;
        $this->related_products = $related_products;
        $this->up_sell_products = $up_sell_products;
        $this->dimensions = $dimensions;
        $this->dimension_notifications = $dimension_notifications;
    }

    public static function fromDataTransferObject(ProductDataTransferObject $dataTransferObject)
    {
        if ($dataTransferObject->hasExistingProduct()) {
            return self::update($dataTransferObject);
        }

        return self::create($dataTransferObject);
    }

    private static function create(ProductDataTransferObject $dataTransferObject): self
    {
        return new self(
            $dataTransferObject->meta,
            $dataTransferObject->category,
            $dataTransferObject->brand,
            $dataTransferObject->vat,
            $dataTransferObject->stock_status,
            $dataTransferObject->locale,
            $dataTransferObject->hidden,
            $dataTransferObject->type,
            $dataTransferObject->min_width,
            $dataTransferObject->min_height,
            $dataTransferObject->max_width,
            $dataTransferObject->max_height,
            $dataTransferObject->extra_production_width,
            $dataTransferObject->extra_production_height,
            $dataTransferObject->title,
            $dataTransferObject->weight,
            $dataTransferObject->price,
            $dataTransferObject->stock,
            $dataTransferObject->order_quantity,
            $dataTransferObject->from_stock,
            $dataTransferObject->sku,
            $dataTransferObject->ean13,
            $dataTransferObject->isbn,
            $dataTransferObject->summary,
            $dataTransferObject->text,
            $dataTransferObject->dimension_instructions,
            $dataTransferObject->sequence,
            $dataTransferObject->images,
            $dataTransferObject->downloads,
            $dataTransferObject->specification_values,
            $dataTransferObject->specials,
            $dataTransferObject->related_products,
            $dataTransferObject->up_sell_products,
            $dataTransferObject->dimensions,
            $dataTransferObject->dimension_notifications
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getMeta(): ?Meta
    {
        return $this->meta;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function getBrand(): ?Brand
    {
        return $this->brand;
    }

    /**
     * @return Vat
     */
    public function getVat(): ?Vat
    {
        return $this->vat;
    }

    /**
     * @return StockStatus
     */
    public function getStockStatus(): StockStatus
    {
        return $this->stock_status;
    }

    /**
     * @return ProductOption[]
     */
    public function getProductOptions()
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->isNull('parent_product_option_value'));

        return $this->product_options->matching($criteria);
    }

    /**
     * @return ProductOption[]
     */
    public function getProductOptionsWithSubOptions()
    {
        return $this->product_options;
    }

    /**
     * @return Locale
     */
    public function getLocale(): Locale
    {
        return $this->locale;
    }

    /**
     * @return bool
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getMinWidth(): int
    {
        return $this->min_width;
    }

    /**
     * @return int
     */
    public function getMinHeight(): int
    {
        return $this->min_height;
    }

    /**
     * @return int
     */
    public function getMaxWidth(): int
    {
        return $this->max_width;
    }

    /**
     * @return int
     */
    public function getMaxHeight(): int
    {
        return $this->max_height;
    }

    /**
     * @return int
     */
    public function getExtraProductionWidth(): int
    {
        return $this->extra_production_width;
    }

    /**
     * @return int
     */
    public function getExtraProductionHeight(): int
    {
        return $this->extra_production_height;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return mixed
     */
    public function getWeight()
    {
        return $this->weight;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    /**
     * @return int
     */
    public function getStock(): int
    {
        return $this->stock;
    }

    /**
     * @param int $stock
     */
    public function setStock(int $stock)
    {
        $this->stock = $stock;
    }

    /**
     * @return int
     */
    public function getOrderQuantity(): int
    {
        return $this->order_quantity;
    }

    /**
     * @return bool
     */
    public function isFromStock(): bool
    {
        return $this->from_stock;
    }

    /**
     * @param bool $from_stock
     */
    public function setFromStock(bool $from_stock)
    {
        $this->from_stock = $from_stock;
    }

    /**
     * @return string
     */
    public function getSku(): string
    {
        return $this->sku;
    }

    /**
     * @return string
     */
    public function getEan13(): ?string
    {
        return $this->ean13;
    }

    /**
     * @return string
     */
    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    /**
     * @return string
     */
    public function getSummary(): ?string
    {
        return $this->summary;
    }

    /**
     * @return string
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * @return string
     */
    public function getDimensionInstructions(): ?string
    {
        return $this->dimension_instructions;
    }

    /**
     * @return MediaGroup
     */
    public function getImages(): ?MediaGroup
    {
        return $this->images;
    }

    /**
     * @return MediaGroup
     */
    public function getDownloads(): ?MediaGroup
    {
        return $this->downloads;
    }

    public function getSequence(): int
    {
        return $this->sequence;
    }

    public function setSequence($sequence): void
    {
        $this->sequence = $sequence;
    }

    /**
     * @return SpecificationValue[]
     */
    public function getSpecificationValues()
    {
        return $this->specification_values;
    }

    public function removeSpecificationValue(SpecificationValue $specificationValue)
    {
        $this->specification_values->removeElement($specificationValue);
    }

    /**
     * @return ProductSpecial[]
     */
    public function getSpecials()
    {
        return $this->specials;
    }

    /**
     * @return ProductDimension[]
     */
    public function getDimensions()
    {
        return $this->dimensions;
    }

    /**
     * @return ProductDimensionNotification[]
     */
    public function getDimensionNotifications()
    {
        $expr = Criteria::expr();
        $criteria = Criteria::create()->where($expr->isNull('product_option'));

        return $this->dimension_notifications->matching($criteria);
    }

    /**
     * @return Product[]
     */
    public function getRelatedProducts()
    {
        return $this->related_products;
    }

    /**
     * @return Product[]
     */
    public function getUpSellProducts()
    {
        return $this->up_sell_products;
    }

    public function getCreatedOn(): DateTime
    {
        return $this->createdOn;
    }

    public function getEditedOn(): DateTime
    {
        return $this->editedOn;
    }

    public function addRelatedProduct(Product $product)
    {
        $this->related_products->add($product);
    }

    public function removeRelatedProduct(Product $product)
    {
        $this->related_products->removeElement($product);
    }

    /**
     * @return CartValue[]
     */
    public function getCartValues(): array
    {
        return $this->cart_values;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createdOn = $this->editedOn = new DateTime();
    }

    private static function update(ProductDataTransferObject $dataTransferObject)
    {
        $product = $dataTransferObject->getProductEntity();

        $product->meta = $dataTransferObject->meta;
        $product->category = $dataTransferObject->category;
        $product->brand = $dataTransferObject->brand;
        $product->vat = $dataTransferObject->vat;
        $product->stock_status = $dataTransferObject->stock_status;
        $product->locale = $dataTransferObject->locale;
        $product->hidden = $dataTransferObject->hidden;
        $product->type = $dataTransferObject->type;
        $product->min_width = $dataTransferObject->min_width;
        $product->min_height = $dataTransferObject->min_height;
        $product->max_width = $dataTransferObject->max_width;
        $product->max_height = $dataTransferObject->max_height;
        $product->extra_production_width = $dataTransferObject->extra_production_width;
        $product->extra_production_height = $dataTransferObject->extra_production_height;
        $product->title = $dataTransferObject->title;
        $product->weight = $dataTransferObject->weight;
        $product->price = $dataTransferObject->price;
        $product->stock = $dataTransferObject->stock;
        $product->order_quantity = $dataTransferObject->order_quantity;
        $product->from_stock = $dataTransferObject->from_stock;
        $product->sku = $dataTransferObject->sku;
        $product->ean13 = $dataTransferObject->ean13;
        $product->isbn = $dataTransferObject->isbn;
        $product->summary = $dataTransferObject->summary;
        $product->text = $dataTransferObject->text;
        $product->dimension_instructions = $dataTransferObject->dimension_instructions;
        $product->sequence = $dataTransferObject->sequence;
        $product->images = $dataTransferObject->images;
        $product->downloads = $dataTransferObject->downloads;
        $product->specification_values = $dataTransferObject->specification_values;
        $product->specials = $dataTransferObject->specials;
        $product->related_products = $dataTransferObject->related_products;
        $product->up_sell_products = $dataTransferObject->up_sell_products;
        $product->dimensions = $dataTransferObject->dimensions;
        $product->dimension_notifications = $dataTransferObject->dimension_notifications;

        return $product;
    }

    public function getDataTransferObject(): ProductDataTransferObject
    {
        return new ProductDataTransferObject($this);
    }

    /**
     * Get the frontend url based on the parent category
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->category->getUrl() . '/' . $this->meta->getUrl();
    }

    /**
     * Get the product thumbnail
     */
    public function getThumbnail()
    {
        if ($this->getImages() && $this->getImages()->hasConnectedItems()) {
            return $this->getImages()->getFirstConnectedMediaItem();
        }

        return null;
    }

    /**
     * Get the active price if is special or the current price
     *
     * @param bool $includeVat
     *
     * @return float
     */
    public function getActivePrice(bool $includeVat = true): float
    {
        $this->calculateActivePrice();

        $price = $this->activePrice;

        if ($includeVat) {
            $price += $price * $this->vat->getAsPercentage();
        }

        return $price;
    }

    /**
     * Get the old price
     *
     * @param bool $includeVat
     *
     * @return float
     */
    public function getOldPrice(bool $includeVat = true): float
    {
        $price = $this->price;

        if ($includeVat) {
            $price += $price * $this->vat->getAsPercentage();
        }

        return $price;
    }

    /**
     * Get the vat price only
     *
     * @return float
     */
    public function getVatPrice()
    {
        return $this->getActivePrice(false) * $this->vat->getAsPercentage();
    }

    /**
     * Check if product has a special price going on
     *
     * @return boolean
     */
    public function hasActiveSpecialPrice()
    {
        $this->calculateActivePrice();

        return $this->hasActiveSpecialPrice;
    }

    /**
     * Check if the product is in stock
     *
     * @return boolean
     */
    public function inStock(): bool
    {
        if (!$this->isFromStock()) {
            return true;
        }

        return $this->getStock() > 0;
    }

    /**
     * Calculate the active price
     */
    private function calculateActivePrice(): void
    {
        if ($this->activePrice) {
            return;
        }

        $today = (new \DateTime('now'))->setTime(0, 0, 0);
        $price = $this->getPrice();

        if ($this->type == self::TYPE_DIMENSIONS) {
            $criteria = Criteria::create()->orderBy([
                'price' => Criteria::ASC
            ])->setMaxResults(1);

            /**
             * @var ProductDimension
             */
            $dimension = $this->dimensions->matching($criteria)->first();

            if ($dimension) {
                $price = $dimension->getPrice();
            }
        }

        $expr = Criteria::expr();
        $criteria = Criteria::create()->where(
            $expr->andX(
                $expr->lte('startDate', $today),
                $expr->gte('endDate', $today)
            )
        )->orWhere(
            $expr->andX(
                $expr->lte('startDate', $today),
                $expr->isNull('endDate')
            )
        )->setMaxResults(1);

        $specialPrices = $this->specials->matching($criteria);

        if ($specialPrice = $specialPrices->first()) {
            $this->hasActiveSpecialPrice = true;
            $price = $specialPrice->getPrice();
        }

        $this->activePrice = $price;
    }

    /**
     * @return bool
     */
    public function usesDimensions(): bool
    {
        return $this->type == self::TYPE_DIMENSIONS;
    }

    /**
     * @param int $width
     * @param int $height
     * @return ProductDimensionNotification|null
     */
    public function getDimensionNotificationByDimension(int $width, int $height): ?ProductDimensionNotification
    {
        $expr = Criteria::expr();
        $criteria = Criteria::create()->where($expr->lte('width', $width))
            ->orWhere($expr->lte('height', $height))
            ->orderBy(['width' => Criteria::DESC, 'height' => Criteria::DESC])
            ->setMaxResults(1);

        $dimensionNotifications = $this->dimension_notifications->matching($criteria)->first();

        return $dimensionNotifications ? $dimensionNotifications : null;
    }

    /**
     * @param int $width
     * @param int $height
     * @return ProductDimensionNotification[]
     */
    public function getAllDimensionNotificationsByDimension(int $width, int $height): array
    {
        $notifications = [];

        if (!$this->usesDimensions()) {
            return $notifications;
        }

        if ($notification = $this->getDimensionNotificationByDimension($width, $height)) {
            $notifications[] = $notification;
        }

        foreach ($this->product_options as $productOption) {
            $notifications = array_merge(
                $notifications,
                $productOption->getAllDimensionNotificationsByDimension($width, $height)
            );
        }

        return $notifications;
    }
}
