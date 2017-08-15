<?php

namespace Backend\Modules\Catalog\Actions;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Backend\Core\Engine\Base\ActionAdd as BackendBaseActionAdd;
use Backend\Core\Engine\Model as BackendModel;
use Backend\Modules\Catalog\Domain\Specification\SpecificationType;
use Backend\Modules\Catalog\Domain\Specification\Command\Create;
use Backend\Modules\Catalog\Domain\Specification\Event\Created;
use Symfony\Component\Form\Form;

/**
 * This is the add specification-action, it will display a form to create a new specification
 *
 * @author Tim van Wolfswinkel <tim@webleads.nl>
 * @author Willem van Dam <w.vandam@jvdict.nl>
 */
class AddSpecification extends BackendBaseActionAdd
{
    /**
     * Execute the action
     */
    public function execute(): void
    {
        parent::execute();

        $form = $this->getForm();
        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->template->assign('form', $form->createView());

            $this->parse();
            $this->display();

            return;
        }

        $createSpecification = $this->createSpecification($form);

        $this->get('event_dispatcher')->dispatch(
            Created::EVENT_NAME,
            new Created($createSpecification->getSpecificationEntity())
        );

        $this->redirect(
            $this->getBackLink(
                [
                    'report' => 'added',
                    'var' => $createSpecification->title,
                ]
            )
        );
        return;
    }

    private function createSpecification(Form $form): Create
    {
        $createSpecification = $form->getData();

        // The command bus will handle the saving of the brand in the database.
        $this->get('command_bus')->handle($createSpecification);

        return $createSpecification;
    }

    private function getBackLink(array $parameters = []): string
    {
        return BackendModel::createUrlForAction(
            'Specifications',
            null,
            null,
            $parameters
        );
    }

    private function getForm(): Form
    {
        $form = $this->createForm(
            SpecificationType::class,
            new Create()
        );

        $form->handleRequest($this->getRequest());

        return $form;
    }
}
