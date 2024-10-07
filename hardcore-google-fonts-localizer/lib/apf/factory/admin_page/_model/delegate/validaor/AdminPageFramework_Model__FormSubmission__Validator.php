<?php 
/**
	Admin Page Framework v3.8.18 by Michael Uno 
	Generated by PHP Class Files Script Generator <https://github.com/michaeluno/PHP-Class-Files-Script-Generator>
	<http://en.michaeluno.jp/admin-page-framework>
	Copyright (c) 2013-2018, Michael Uno; Licensed under MIT <http://opensource.org/licenses/MIT> */
class AdminPageFramework_Model__FormSubmission__Validator_Base extends AdminPageFramework_Model__FormSubmission_Base {
    public $oFactory;
    public $sHookType = 'action';
    public $sActionHookPrefix = 'try_validation_before_';
    public $iHookPriority = 10;
    public $iCallbackParameters = 5;
    public $sCallbackName = '_replyToCallback';
    public function __construct($oFactory) {
        $this->oFactory = $oFactory;
        $_sFunctionName = 'action' === $this->sHookType ? 'add_action' : 'add_filter';
        $_sFunctionName($this->sActionHookPrefix . $this->oFactory->oProp->sClassName, array($this, $this->sCallbackName), $this->iHookPriority, $this->iCallbackParameters);
    }
    protected function _confirmSubmitButtonAction($sPressedInputName, $sSectionID, $sType = 'reset') {
        switch ($sType) {
            default:
            case 'reset':
                $_sFieldErrorMessage = $this->oFactory->oMsg->get('reset_options');
                $_sTransientKey = 'apf_rc_' . md5($sPressedInputName . get_current_user_id());
            break;
            case 'email':
                $_sFieldErrorMessage = $this->oFactory->oMsg->get('send_email');
                $_sTransientKey = 'apf_ec_' . md5($sPressedInputName . get_current_user_id());
            break;
        }
        $_aNameKeys = explode('|', $sPressedInputName) + array('', '', '');
        $_sFieldID = $this->getAOrB($sSectionID, $_aNameKeys[2], $_aNameKeys[1]);
        $_aErrors = array();
        if ($sSectionID && $_sFieldID) {
            $_aErrors[$sSectionID][$_sFieldID] = $_sFieldErrorMessage;
        } else if ($_sFieldID) {
            $_aErrors[$_sFieldID] = $_sFieldErrorMessage;
        }
        $this->oFactory->setFieldErrors($_aErrors);
        $this->setTransient($_sTransientKey, $sPressedInputName, 60 * 2);
        $this->oFactory->setSettingNotice($this->oFactory->oMsg->get('confirm_perform_task'), 'error confirmation');
        return $this->oFactory->oProp->aOptions;
    }
}
class AdminPageFramework_Model__FormSubmission__Validator extends AdminPageFramework_Model__FormSubmission__Validator_Base {
    public $oFactory;
    public $aInputs = array();
    public $aRawInputs = array();
    public $aOptions = array();
    public function __construct($oFactory) {
        $this->oFactory = $oFactory;
        add_filter("validation_pre_" . $this->oFactory->oProp->sClassName, array($this, '_replyToValidateUserFormInputs'), 10, 4);
    }
    public function _replyToValidateUserFormInputs($aInputs, $aRawInputs, $aOptions, $oFactory) {
        $_sTabSlug = $this->getElement($_POST, 'tab_slug', '');
        $_sPageSlug = $this->getElement($_POST, 'page_slug', '');
        $_aSubmits = $this->getElementAsArray($_POST, '__submit', array());
        $_sPressedInputName = $this->_getPressedSubmitButtonData($_aSubmits, 'name');
        $_sSubmitSectionID = $this->_getPressedSubmitButtonData($_aSubmits, 'section_id');
        $_aSubmitsInformation = array('page_slug' => $_sPageSlug, 'tab_slug' => $_sTabSlug, 'input_id' => $this->_getPressedSubmitButtonData($_aSubmits, 'input_id'), 'section_id' => $_sSubmitSectionID, 'field_id' => $this->_getPressedSubmitButtonData($_aSubmits, 'field_id'), 'input_name' => $_sPressedInputName,);
        $_aClassNames = array('AdminPageFramework_Model__FormSubmission__Validator__Link', 'AdminPageFramework_Model__FormSubmission__Validator__Redirect', 'AdminPageFramework_Model__FormSubmission__Validator__Import', 'AdminPageFramework_Model__FormSubmission__Validator__Export', 'AdminPageFramework_Model__FormSubmission__Validator__Reset', 'AdminPageFramework_Model__FormSubmission__Validator__ResetConfirm', 'AdminPageFramework_Model__FormSubmission__Validator__ContactForm', 'AdminPageFramework_Model__FormSubmission__Validator__ContactFormConfirm',);
        foreach ($_aClassNames as $_sClassName) {
            new $_sClassName($this->oFactory);
        }
        try {
            $this->addAndDoActions($this->oFactory, 'try_validation_before_' . $this->oFactory->oProp->sClassName, $aInputs, $aRawInputs, $_aSubmits, $_aSubmitsInformation, $this->oFactory);
            $_oFormSubmissionFilter = new AdminPageFramework_Model__FormSubmission__Validator__Filter($this->oFactory, $aInputs, $aRawInputs, $aOptions, $_aSubmitsInformation);
            $aInputs = $_oFormSubmissionFilter->get();
            $this->addAndDoActions($this->oFactory, 'try_validation_after_' . $this->oFactory->oProp->sClassName, $aInputs, $aRawInputs, $_aSubmits, $_aSubmitsInformation, $this->oFactory);
        }
        catch(Exception $_oException) {
            $_sPropertyName = $_oException->getMessage();
            if (isset($_oException->$_sPropertyName)) {
                $this->_setSettingNoticeAfterValidation(empty($_oException->{$_sPropertyName}));
                return $_oException->{$_sPropertyName};
            }
            return array();
        }
        $this->_setSettingNoticeAfterValidation(empty($aInputs));
        return $aInputs;
    }
}
