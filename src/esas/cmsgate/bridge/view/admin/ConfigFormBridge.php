<?php


namespace esas\cmsgate\bridge\view\admin;


use esas\cmsgate\lang\Translator;
use esas\cmsgate\utils\CMSGateException;
use esas\cmsgate\utils\htmlbuilder\Attributes as attribute;
use esas\cmsgate\utils\htmlbuilder\Elements as element;
use esas\cmsgate\utils\htmlbuilder\presets\BootstrapPreset as bootstrap;
use esas\cmsgate\utils\UploadedFileWrapper;
use esas\cmsgate\view\admin\AdminViewFields;
use esas\cmsgate\view\admin\ConfigFormHtml;
use esas\cmsgate\view\admin\fields\ConfigField;
use esas\cmsgate\view\admin\fields\ConfigFieldCheckbox;
use esas\cmsgate\view\admin\fields\ConfigFieldFile;
use esas\cmsgate\view\admin\fields\ConfigFieldList;
use esas\cmsgate\view\admin\fields\ConfigFieldPassword;
use esas\cmsgate\view\admin\fields\ConfigFieldTextarea;
use esas\cmsgate\view\admin\fields\ListOption;

class ConfigFormBridge extends ConfigFormHtml
{
    private $orderStatuses;

    public function __construct($managedFields, $formKey, $submitUrl, $submitButtons = null) {
        parent::__construct($managedFields, $formKey, $submitUrl, $submitButtons);
        $this->orderStatuses['pending'] = new ListOption('pending', 'Pending');
        $this->orderStatuses['payed'] = new ListOption('payed', 'Payed');
        $this->orderStatuses['failed'] = new ListOption('failed', 'Failed');
        $this->orderStatuses['canceled'] = new ListOption('canceled', 'Canceled');
    }

    protected $footerButtons;

    public function addFooterButton($label, $href, $classAppend = '') {
        $this->footerButtons .= element::a(
            attribute::href($href),
            attribute::clazz('btn me-1 ' . $classAppend),
            element::content($label)
        );
    }

    public function addFooterButtonCancel($redirectHref) {
        $this->addFooterButton(Translator::fromRegistry()->translate(AdminViewFields::CANCEL), $redirectHref, 'btn-secondary');
    }

    public function addFooterButtonDelete($redirectHref) {
        $this->addFooterButton(Translator::fromRegistry()->translate(AdminViewFields::DELETE), $redirectHref, 'btn-secondary');
    }

    protected $hiddenInput;

    public function addHiddenInput($key, $value) {
        $this->hiddenInput .= element::input(
            attribute::name($key),
            attribute::type('hidden'),
            attribute::id($key),
            attribute::value($value)
        );
    }

    public function generate() {
        return
            element::form(
                ($this->getSubmitUrl() != null ? attribute::action($this->getSubmitUrl()) : ""),
                attribute::method("post"),
                attribute::enctype("multipart/form-data"),
                attribute::id("config-form"),
                $this->elementConfigPanel()
            );

    }

    protected function elementConfigPanel() {
        return bootstrap::elementCard(
            bootstrap::elementCardHeader($this->getHeadingTitle()),
            bootstrap::elementCardBody(
                $this->hiddenInput,
                parent::generate()
            ),
            bootstrap::elementCardFooter(
                bootstrap::elementCardFooterButtons(
                    $this->elementSubmitButtons(),
                    $this->footerButtons
                )
            )
        );
    }

    protected function elementInputSubmit($name, $value) {
        return
            element::input(
                attribute::clazz("btn btn-secondary"),
                attribute::type("submit"),
                attribute::name($name),
                attribute::value($value)
            );
    }

    public static function elementFormGroup(ConfigField $configField, $input, ...$extraElements) {
        return bootstrap::formGroup(
            self::elementLabel($configField),
            element::div(
                attribute::clazz("col"),
                $input,
                self::elementInputValidationDetails($configField)
            ),
            $extraElements
        );
    }

    public static function elementInputValidationDetails(ConfigField $configField) {
        $validationResult = $configField->getValidationResult();
        if ($validationResult == null || $validationResult->isValid())
            return '';
        else
            return element::small(
                attribute::clazz('text-danger'),
                $validationResult->getErrorTextSimple()
            );
    }

    function generateTextField(ConfigField $configField) {
        return
            self::elementFormGroup(
                $configField,
                self::elementInput($configField, "text")
            );
    }

    public function generatePasswordField(ConfigFieldPassword $configField) {
        return
            self::elementFormGroup(
                $configField,
                self::elementInput($configField, "password")
            );
    }

    public function generateTextAreaField(ConfigFieldTextarea $configField) {
        return
            self::elementFormGroup(
                $configField,
                element::textarea(
                    $configField->getCols() != null ? attribute::cols($configField->getCols()) : "",
                    attribute::rows($configField->getRows()),
                    attribute::clazz("form-control "),
                    attribute::type("textarea"),
                    attribute::name($configField->getKey()),
                    attribute::id($configField->getKey()),
                    element::content($configField->getValue())
                )
            );
    }


    public function generateFileField(ConfigFieldFile $configField) {
        throw new CMSGateException("Not implemented");
    }

    private function getFileColor($fileName) {
        $file = new UploadedFileWrapper($fileName);
        return $file->isExists() ? "green" : "red";
    }

    public function generateCheckboxField(ConfigFieldCheckbox $configField) {
        return
            self::elementFormGroup(
                $configField,
                element::input(
                    bootstrap::isBootstrapV5() ? attribute::clazz('form-check-input') : "",
                    attribute::type("checkbox"),
                    attribute::name($configField->getKey()),
                    attribute::value("yes"),
                    attribute::checked($configField->isChecked())
                )
            );
    }

    public function generateListField(ConfigFieldList $configField) {
        return
            self::elementFormGroup(
                $configField,
                element::select(
                    attribute::clazz("form-control"),
                    attribute::name($configField->getKey()),
                    attribute::id($configField->getKey()),
                    parent::elementOptions($configField)
                )
            );
    }


    public static function elementLabel(ConfigField $configField) {
        return
            element::label(
                attribute::forr($configField->getKey()),
                attribute::clazz("col-sm-2 col-form-label"),
                attribute::data_toggle("tooltip"),
                attribute::data_placement("left"),
                attribute::title($configField->getDescription()),
                element::content($configField->getName()),
                element::span(
                    attribute::data_toggle("tooltip"),
                    attribute::title($configField->getDescription())

                )
            );
    }

    public static function elementInput(ConfigField $configField, $type) {
        return
            element::input(
                attribute::clazz("form-control " . ($configField->isValid() ? "" : "border-danger")),
                attribute::name($configField->getKey()),
                attribute::type($type),
                attribute::readOnly($configField->isReadOnly()),
                attribute::id($configField->getKey()),
                attribute::placeholder($configField->getName()),
                attribute::value($configField->getValue())
            );
    }

    public static function elementValidationError(ConfigField $configField) {
        $validationResult = $configField->getValidationResult();
        if ($validationResult != null && !$validationResult->isValid())
            return
                element::p(
                    element::font(
                        attribute::color("red"),
                        element::content($validationResult->getErrorTextSimple())
                    )
                );
        else
            return "";
    }


    /**
     * @return ListOption[]
     */
    public function createStatusListOptions() {
        return $this->orderStatuses;
    }
}