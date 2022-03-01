<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model\DbClass;
use Nette;
use Nette\Application\UI\Form;
use dibi;


final class NamesPresenter extends Nette\Application\UI\Presenter
{
    private $database;

    // pro práci s vrstvou Database Explorer si předáme Nette\Database\Explorer
    public function __construct(Dibi\Connection $database)
    {
        $this->database = $database;

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

        for ($x = 0; $x < 7; $x++) {
            $form->addText('jmenotymu'.$x, 'Jméno týmu');
//                ->setHtmlType('tym');
        }

//

        $form->addSubmit('zapis', 'zapiš')
            ->setHtmlAttribute('class', 'zapis');

        $form->onSuccess[] = [$this, 'JmenaSucceeded'];
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
    public function JmenaSucceeded(Form $form)
    {
        $dbtest = new DbClass($this->database);
        $values = (array)$form->getValues();
//        $dbtest->saveTeamNames($values);
        $dbtest->makeTeamTable($values);
        dump($values); exit;
        $fp = fopen('otherFiles/result.csv', 'w');
        foreach ($values as $key =>$fields) {
            fwrite($fp, $fields."\n");
        }

        fclose($fp);
        $this->flashMessage('Tabulka vytvořena.');
//        $this->redirect('Results:');
    }

}
