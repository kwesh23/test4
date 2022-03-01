<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Dibi;
use Nette\Application\UI\Form;
use Nette\Mail\Message;
use Psr\Log\LoggerInterface;


final class RegistracePresenter extends Nette\Application\UI\Presenter
{
    /** @var LoggerInterface **/
    private $logger;


//    public function injectLogger(LoggerInterface $logger): void
//    {
//        $this->logger = $logger;
//    }


    public function createComponentRegistrationForm(): Form
    {
        $form = new Form();
//        $form->onRender[] = $this->makeBootstrap4($form);

        $form->onRender[] = function ($form){
            $this->makeBootstrap4($form);
        };

        $form->addText('name', 'Jméno:')
        ->setRequired('Povinné pole');

        $form->addText('surname', 'Příjmení:')
        ->setRequired('Povinné pole');
        $form->addEmail('email', 'Email:')
        ->setRequired('Povinné pole');
        $form->addSubmit('send', 'Přihlásit na Herakleidy');

        $form->onSuccess[] = [$this, 'formSucceeded'];

        return $form;
    }

    public function formSucceeded(Form $form, $data): void
    {
        $mail = new Message();
        $mail->addTo('sasa.fuchs@gmail.com')
            ->setSubject('Herakleidy Potvrzení ');
        // tady zpracujeme data odeslaná formulářem
        // $data->name obsahuje jméno
        // $data->password obsahuje heslo
        $mail->setFrom('herakleidy@tak23.cz', 'Herakleidy');
        $mail->setHtmlBody('<p>Dobrý den,</p><p>vaše objednávka byla přijata.</p>');
        $this->flashMessage('Byl jste úspěšně registrován.');

        $mailer = new Nette\Mail\SmtpMailer([
            'host' => 'smtp.tak23.cz',
            'username' => 'herakleidy@tak23.cz',
            'password' => '23ASdf2323',
            'secure' => 'ssl'
        ]);
        $mailer->send($mail);
        $this->redirect('Homepage:');
    }

    function makeBootstrap4(Form $form): void
    {
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'div class="form-group row"';
        $renderer->wrappers['pair']['.error'] = 'has-danger';
        $renderer->wrappers['control']['container'] = 'div class=col-sm-9';
        $renderer->wrappers['label']['container'] = 'div class="col-sm-3 col-form-label"';
        $renderer->wrappers['control']['description'] = 'span class=form-text';
        $renderer->wrappers['control']['errorcontainer'] = 'span class=form-control-feedback';
        $renderer->wrappers['control']['.error'] = 'is-invalid';

        foreach ($form->getControls() as $control) {
            $type = $control->getOption('type');
            if ($type === 'button') {
                $control->getControlPrototype()->addClass(empty($usedPrimary) ? 'btn btn-primary' : 'btn btn-secondary');
                $usedPrimary = true;

            } elseif (in_array($type, ['text', 'textarea', 'select'], true)) {
                $control->getControlPrototype()->addClass('form-control');

            } elseif ($type === 'file') {
                $control->getControlPrototype()->addClass('form-control-file');

            } elseif (in_array($type, ['checkbox', 'radio'], true)) {
                if ($control instanceof Nette\Forms\Controls\Checkbox) {
                    $control->getLabelPrototype()->addClass('form-check-label');
                } else {
                    $control->getItemLabelPrototype()->addClass('form-check-label');
                }
                $control->getControlPrototype()->addClass('form-check-input');
                $control->getSeparatorPrototype()->setName('div')->addClass('form-check');
            }
        }
    }

    function makeBootstrap4a(Form $form): void
    {
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = NULL;
        $renderer->wrappers['pair']['container'] = 'div class="form-group row"';
        $renderer->wrappers['pair']['.error'] = 'has-danger';
        $renderer->wrappers['control']['container'] = 'div class=col-sm-12';
        $renderer->wrappers['label']['container'] = 'div class="col-sm-12 col-form-label"';
        $renderer->wrappers['control']['description'] = 'span class=form-text';
        $renderer->wrappers['control']['errorcontainer'] = 'span class="form-control-feedback own"';

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
    }


}
