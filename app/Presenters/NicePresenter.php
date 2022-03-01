<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model\DbClass;
use Nette;
use Nette\Application\UI\Form;
use Dibi;
use Nette\Mail\Message;
use Psr\Log\LoggerInterface;
use UserFormRules;

final class NicePresenter extends Nette\Application\UI\Presenter
{

	/** @var LoggerInterface * */
	private $logger;

	/**
	 * @var array
	 */
	private $config;

	private $database;

	public function __construct(array $config, LoggerInterface $logger, Dibi\Connection $database)
	{
		$this->config = $config;
		$this->logger = $logger;
		$this->database = $database;
	}

	public function renderDefault()
	{
		$this->template->kuna = 'kuna';

		$ucastnici = $this->getAllVisiblePlayers();
		$this->template->ucastnici = $ucastnici;
	}

	public function createComponentRegistrationForm(): Form
	{
		$form = new Form();

		$form->onRender[] = function ($form) {
			$this->makeBootstrap4($form);
		};

		$form->addText('name', 'Jméno:')
			->setHtmlAttribute('name', 'firstname')
			->setHtmlAttribute('id', 'firstname')
			->setHtmlAttribute('placeHolder', 'Jméno')
			->setRequired('Povinné pole');

		$form->addText('surname', 'Příjmení:')
			->setHtmlAttribute('name', 'surname')
			->setHtmlAttribute('id', 'surname')
			->setHtmlAttribute('placeHolder', 'Příjmení')
			->setRequired('Povinné pole');
		$form->addEmail('email', 'Email:')
			->setHtmlAttribute('name', 'pemail')
			->setHtmlAttribute('id', 'pemail')
			->setHtmlAttribute('placeHolder', 'Email')
			->setRequired('Povinné pole');
		$form->addText('tel', 'tel')
			->setHtmlAttribute('name', 'tel')
			->setHtmlAttribute('id', 'tel')
			->setHtmlAttribute('placeHolder', 'Telefon')
			->addRule(Nette\Forms\Form::PATTERN, 'zadej telefon ve formatu +420XXXXXXXXX nebo jen XXXXXXXXX', '^(\+?420)?(2[0-9]{2}|3[0-9]{2}|4[0-9]{2}|5[0-9]{2}|72[0-9]|73[0-9]|77[0-9]|60[1-8]|56[0-9]|70[2-5]|79[0-9])[0-9]{3}[0-9]{3}$')
			->setRequired('Povinné pole');

		$form->addTextArea('note', 'Poznámka')
			->setHtmlAttribute('name', 'note')
			->setHtmlAttribute('id', 'note')
			->setHtmlAttribute('placeHolder', 'Poznámka')
			->setHtmlAttribute('rows', '5');
		$form->addSubmit('send', 'Přihlásit na Herakleidy');
		$form->addCheckbox('displayName', 'Souhlasím, aby mé jméno bylo zobrazeno na těchto stránkách v sekci účastníci.');

		$form->addCheckbox('agreement', 'Souhlasím se zpracováním osobních údajů z tohoto formuláře pro účely akce Herakleidy.')
			->setRequired('Povinné pole');
		$form->getElementPrototype()
			->setAttribute('role', 'form')
			->class('php-email-form');
		$form->onSuccess[] = [$this, 'formSucceeded'];

		return $form;
	}

	public function formSucceeded(Form $form, $data): void
	{
		$values = $form->getValues();
		$valuesArray = (array)$values;
		$url = 'https://www.google.com/recaptcha/api/siteverify';
		$privateKey= $this->config['privateKey'];
		if(isset($_POST['g-recaptcha-response'])){
			$captcha=$_POST['g-recaptcha-response'];
		}
		if($captcha == ""){
			$this->flashMessage('google recaptha' );
			$this->presenter->redirect('this#prihlaska');
			return;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url."?secret=".$privateKey."&response=".$_POST['g-recaptcha-response']."&remoteip=".$_SERVER['REMOTE_ADDR']);
		$result = curl_exec($ch);
		curl_close($ch);

		$obj = json_decode($result);
		if($obj->success) {
			$db = new DbClass($this->database);
			try {
				$emailexist = $db->isEmailUse($values->email);
				if ($emailexist) {
					$this->logger->info('znovu registrace emailu ' . $values->email);
					$this->flashMessage('Účastník s emailem ' . $values->email . ' je již registrován', 'danger');
				} else {
					if ($values && $db) {
						$countBefore = $db->getPlayersCount();

						$db->saveNewPlayer($values, $countBefore);
						$count = $db->getPlayersCount();
						$values->offsetSet('count', $count);
//					if ($_SERVER['HTTP_HOST'] != 'localhost') {
						$this->sendPlayerEmails($values);
						$this->sendOrgEmails($values);
						$this->flashMessage('Byl jste úspěšně registrován na Herakleidy 2022. Další informace budou zaslány na tvůj e-mail ' . $values->email);
//					}
					}
				}

			} catch (\Exception $e) {
				$this->flashMessage('Něco se dramaticky pokazilo, Zkus to prosím později.', 'danger');
				$this->logger->error($e->getMessage());
			}
		}else{
			$this->flashMessage('Něco se dramaticky pokazilo, Zkus to prosím později.', 'danger');
			$this->logger->error('recaptcha');
		}
		$this->presenter->redirect('this#prihlaska');

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

	/**
	 * @param Nette\Utils\ArrayHash $values
	 * @return bool
	 */

	public function sendOrgEmails(Nette\Utils\ArrayHash $values): bool
	{

		$latte = new \Latte\Engine();
		$latte->renderToString(__DIR__ . '/templates/Nice/email.latte', $values);
		$mail = new Message();
		$mail
			->addTo('sasa.fuchs@gmail.com')
//				->addTo('al-f@volny.cz')
			->setSubject('Herakleidy: Potvrzení přihlášky');
		$mail->setFrom('herakleidy@tak23.cz', 'Herakleidy');
		$mail->setHtmlBody($latte->renderToString(__DIR__ . '/templates/Nice/email.latte', $values));
		try {
			$mailer = new Nette\Mail\SmtpMailer([
				'host' => $this->config['emailHost'],
				'username' => $this->config['emailUserName'],
				'password' => $this->config['emailPassword'],
				'secure' => $this->config['emailsSecure']
			]);
			$mailer->send($mail);
			return true;
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage());
			return false;
		}

	}

	public function sendPlayerEmails(Nette\Utils\ArrayHash $values): bool
	{
		$latte = new \Latte\Engine();
		$latte->renderToString(__DIR__ . '/templates/Nice/email.latte', $values);
		$mail = new Message();
		$mail->addTo($values->email)
			->setSubject('Herakleidy: Potvrzení přihlášky');
		$mail->setFrom('herakleidy@tak23.cz', 'Herakleidy');

		$mail->setHtmlBody($latte->renderToString(__DIR__ . '/templates/Nice/email-player.latte', (array)$values));
		try {
			$mailer = new Nette\Mail\SmtpMailer([
				'host' => $this->config['emailHost'],
				'username' => $this->config['emailUserName'],
				'password' => $this->config['emailPassword'],
				'secure' => $this->config['emailsSecure']
			]);
			$mailer->send($mail);
			return true;
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage());
			return false;
		}

	}

	public function getAllPlayers()
	{
		try {
			$db = new DbClass($this->database);
			return $db->getAllPlayers();
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage());
		}

	}

	public function getAllVisiblePlayers()
	{
		try {
			$db = new DbClass($this->database);
			return $db->getAllVisiblePlayers();
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage());
		}

	}
}
