<?php

class UserFormRules
{
    const PHONE = 'UserFormRules::validatePhone';
    const EMAIL_DOMAIN = 'UserFormRules::validateEmailDomain';

    public static function validatePhone($control)
    {
        $cistecislo = str_replace(' ', '', $control->getValue());
        $jetotak = preg_match("/^((\\+420)|(00420))?[0-9]{9}\$/", $cistecislo);
        return $jetotak;
        
        // validace uživatelského jména
    }

    public static function validateEmailDomain(IControl $control, $domain)
    {
        // validace, zda se jedné o e-mail z domény $domain
    }
}