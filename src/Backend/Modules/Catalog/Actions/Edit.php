<?php

namespace Backend\Modules\Catalog\Actions;

use Backend\Core\Language\Locale;
use Backend\Form\Type\DeleteType;
use Backend\Core\Engine\Base\ActionEdit as BackendBaseActionEdit;
use Backend\Core\Engine\Model as BackendModel;
use Backend\Modules\Catalog\Domain\Product\Command\UpdateProduct;
use Backend\Modules\Catalog\Domain\Product\Event\Updated;
use Backend\Modules\Catalog\Domain\Product\Exception\ProductNotFound;
use Backend\Modules\Catalog\Domain\Product\Product;
use Backend\Modules\Catalog\Domain\Product\ProductRepository;
use Backend\Modules\Catalog\Domain\Product\ProductType;
use Backend\Modules\Catalog\Domain\ProductDimension\ProductDimension;
use Backend\Modules\Catalog\Domain\ProductOption\DataGrid as ProductOptionDataGrid;
use Symfony\Component\Form\Form;

/**
 * This is the edit-action, it will display a form with the product data to edit
 *
 * @author Tim van Wolfswinkel <tim@webleads.nl>
 * @author Jacob van Dam <j.vandam@jvdict.nl>
 */
class Edit extends BackendBaseActionEdit
{
    /**
     * @var Product
     */
    private $product;

    /**
     * Execute the action
     */
    public function execute(): void
    {
        parent::execute();

        $this->product = $this->getProduct();

        $form = $this->getForm($this->product);

        $deleteForm = $this->createForm(
            DeleteType::class,
            ['id' => $this->product->getId()],
            [
                'module' => $this->getModule(),
            ]
        );
        $this->template->assign('deleteForm', $deleteForm->createView());

        $copyForm = $this->get('form.factory')->createNamed(
            'copy',
            DeleteType::class,
            ['id' => $this->product->getId()],
            [
                'module' => $this->getModule(),
                'action' => 'Copy',
                'block_name' => 'copy',
            ]
        );
        $this->template->assign('copyForm', $copyForm->createView());

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->template->assign('form', $form->createView());
            $this->template->assign('product', $this->product);
            $this->template->assign('productOptionsDataGrid', ProductOptionDataGrid::getHtml($this->product));

            $this->parse();
            $this->display();

            return;
        }

        /** @var UpdateProduct $updateProduct */
        $updateProduct = $this->updateProduct($form);

        $this->get('event_dispatcher')->dispatch(
            Updated::EVENT_NAME,
            new Updated($updateProduct->getProductEntity())
        );

        $this->redirect(
            $this->getBackLink(
                [
                    'report' => 'edited',
                    'var' => $updateProduct->title,
                    'highlight' => 'row-' . $updateProduct->getProductEntity()->getId(),
                ]
            )
        );
    }

    protected function parse(): void
    {
        parent::parse();

        $this->header->addJS(
            '/js/vendors/select2.full.min.js',
            null,
            true,
            true
        );

        $this->header->addJS(
            '/js/vendors/' . Locale::workingLocale() . '.js',
            null,
            true,
            true
        );

        $this->header->addJS('Select2Entity.js');
        $this->header->addJS('ProductDimensions.js');

        $this->header->addCSS(
            '/css/vendors/select2.min.css',
            null,
            true,
            false
        );
        $this->header->addCSS('ProductDimensions.css');

        $this->header->addJsData($this->getModule(), 'types', [
            'default' => Product::TYPE_DEFAULT,
            'dimensions' => Product::TYPE_DIMENSIONS,
        ]);
    }

    private function getProduct(): Product
    {
        /** @var ProductRepository $productRepository */
        $productRepository = $this->get('catalog.repository.product');

        try {
            return $productRepository->findOneByIdAndLocale(
                $this->getRequest()->query->getInt('id'),
                Locale::workingLocale()
            );
        } catch (ProductNotFound $e) {
            $this->redirect($this->getBackLink(['error' => 'non-existing']));
        }
    }

    private function getBackLink(array $parameters = []): string
    {
        return BackendModel::createUrlForAction(
            'Index',
            null,
            null,
            $parameters
        );
    }

    private function getForm(Product $product): Form
    {
        $form = $this->createForm(
            ProductType::class,
            new UpdateProduct($product),
            [
                'categories' => $this->get('catalog.repository.category')->getTree(Locale::workingLocale()),
                'product' => $product,
                'validation_groups' => $this->get('catalog.form.product_validation_resolver'),
            ]
        );

        $form->handleRequest($this->getRequest());

        return $form;
    }

    private function updateProduct(Form $form): UpdateProduct
    {
        /** @var UpdateProduct $updateProduct */
        $updateProduct = $form->getData();

        // The command bus will handle the saving of the product in the database.
        $this->get('command_bus')->handle($updateProduct);

        return $updateProduct;
    }
}
