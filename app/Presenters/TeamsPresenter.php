<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;


final class TeamsPresenter extends Nette\Application\UI\Presenter
{

    private $database;

    // pro práci s vrstvou Database Explorer si předáme Nette\Database\Explorer
    public function __construct(Nette\Database\Connection $database)
    {
        $this->database = $database;
        dump($this->database);
    }

    protected function createComponentRegistrationForm(): Form
    {
        $form = new Form;
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = NULL;
        $renderer->wrappers['pair']['container'] = 'div class="form-group row"';
        $renderer->wrappers['pair']['.error'] = 'has-danger';
        $renderer->wrappers['control']['container'] = 'div class=col-sm-12';
        $renderer->wrappers['label']['container'] = 'div class="col-sm-12 col-form-label"';
        $renderer->wrappers['control']['description'] = 'span class=form-text';
        $renderer->wrappers['control']['errorcontainer'] = 'span class="form-control-feedback own"';

        $form->onRender[] = function ($form) {
            foreach ($form->getControls() as $control) {
                $type = $control->getOption('type');
                if ($type === 'button') {
                    $control->getControlPrototype()->addClass(empty($usedPrimary) ? 'btn btn-primary' : 'btn btn-secondary');
                    $usedPrimary = TRUE;

                } elseif (in_array($type, ['text', 'textarea', 'select'], TRUE)) {
                    $control->getControlPrototype()->addClass('form-control');

                } elseif ($type === 'file') {
                    $control->getControlPrototype()->addClass('form-control-file');

                } elseif (in_array($type, ['checkbox', 'radio'], TRUE)) {
                    if ($control instanceof Nette\Forms\Controls\Checkbox) {
                        $control->getLabelPrototype()->addClass('form-check-label');
                    } else {
                        $control->getItemLabelPrototype()->addClass('form-check-label');
                    }
                    $control->getControlPrototype()->addClass('form-check-input');
                    $control->getSeparatorPrototype()->setName('div')->addClass('form-check');
                }
            }
        };

        $pocetTymu = [
            '1' => 1,
            '2' => 2,
            '3' => 3,
            '4' => 4,
            '5' => 5,
            '6' => 6,
            '7' => 7,
            '8' => 8,
            '9' => 9,
            '10' => 10,
        ];
        $tymy = $form->addSelect('pocetTymu', 'Počet týmů:', $pocetTymu)
            ->setPrompt('----');
        $form->onAnchor[] = fn() =>
        $aaa = $tymy->getValue();
        ;
//        dump($aaa);
        foreach ($pocetTymu as $item) {
            $form->addText('name'.$item, 'Heslo'.$item)
            ->setRequired('Zadejte prosím jméno týmu'.$item);
        }
//        $form->addText('name', 'Jméno:');
//        $form->addPassword('password', 'Heslo:');
        $form->addSubmit('send', 'Registrovat');
        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function formSucceeded(Form $form, $data): void
    {
        // tady zpracujeme data odeslaná formulářem
        // $data->name obsahuje jméno
        // $data->password obsahuje heslo
        $this->flashMessage('Byl jste úspěšně registrován.');
//        $this->redirect('Homepage:');
    }

}
