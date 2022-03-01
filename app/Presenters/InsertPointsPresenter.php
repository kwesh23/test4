<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model\DbClass;
use Nette;
use Nette\Application\UI\Form;
use Dibi;

final class InsertPointsPresenter extends Nette\Application\UI\Presenter
{
    private $database;

    // pro práci s vrstvou Database Explorer si předáme Nette\Database\Explorer
    public function __construct(Dibi\Connection $database)
    {
        $this->database = $database;

    }
    protected function createComponentSetPointsForm(): Form
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
        $dbtest = new DbClass($this->database);
        $teams = $dbtest->getAllFromTable('tymy');
        $form->addText('gameName', 'Jméno hry:')
        ->setHtmlAttribute('class','headerInput')
            ->setRequired('prase');
        foreach ($teams as $team) {
            $form->addInteger('gamePoints'.$team->id_team, 'Početbodu pro '.$team->name_team )
            ->addRule($form::MAX, 'maximalne 10', 10)
            ->setRequired();
//                ->setHtmlType('tym');
        }

//

        $form->addSubmit('zapis', 'zapiš')
            ->setHtmlAttribute('class', 'zapis');

        $form->onSuccess[] = [$this, 'setPointsFormSucceeded'];
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
    public function setPointsFormSucceeded(Form $form)
    {

        try{
            $values = (array)$form->getValues();
            $id = 0;
            $dbtest = new DbClass($this->database);
            $exist = $dbtest->gameExist($values['gameName']);
            if (!empty($values['gameName'] && !$exist)){
                $id = $dbtest->saveGameName($values['gameName']);
                $points = $values;
                foreach (array_keys($points, $values['gameName'], true) as $key) {
                    unset($points[$key]);
                }
                if (!empty($values['gameName'] && $id && $id != 0)){
                    $dbtest->saveGamePoints($points, $id);
                }
                $this->flashMessage('Zapsalo.', 'success');
            }else{
                $this->flashMessage('Jmeno teto hry jiz existuje, zadej jine.', 'danger');
            }
        }catch (\Exception $e){
            $this->flashMessage('Posralo.', 'danger');
        }
    }


}
