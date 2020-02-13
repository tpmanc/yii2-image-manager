<?php

namespace noam148\imagemanager\components;

use Yii;
use yii\widgets\InputWidget;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use noam148\imagemanager\models\ImageManager;
use noam148\imagemanager\assets\ImageManagerInputAsset;

class ImageManagerInputWidget extends InputWidget {

    /**
     * @var null|integer The aspect ratio the image needs to be cropped in (optional)
     */
    public $aspectRatio = null; //option info: https://github.com/fengyuanchen/cropper/#aspectratio

    /**
     * @var int Define the viewMode of the cropper
     */
    public $cropViewMode = 1; //option info: https://github.com/fengyuanchen/cropper/#viewmode

    /**
     * @var bool Show a preview of the image under the input
     */
    public $showPreview = true;

    /**
     * @var bool Show a confirmation message when de-linking a image from the input
     */
    public $showDeletePickedImageConfirm = false;

    /**
     * @var bool
     */
    public $multiple = false;

    /**
     * @inheritdoc
     */
    public function init() {
        parent::init();
        //set language
        if (!isset(Yii::$app->i18n->translations['imagemanager'])) {
            Yii::$app->i18n->translations['imagemanager'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'sourceLanguage' => 'en',
                'basePath' => __DIR__ . '/imagemanager/messages'
            ];
        }
    }

    /**
     * @inheritdoc
     */
    public function run() {
        //default
        $ImageManager_id = null;
        $mImageManager = null;
        $sFieldId = null;
        //start input group
        $field = "<div class='image-manager-input'>";
        $field .= "<div class='input-group'>";
        //set input fields
        if ($this->hasModel()) {
            //get field id
            $sFieldId = Html::getInputId($this->model, $this->attribute);
            $sFieldNameId = $sFieldId . "_name";
            //get attribute name
            $sFieldAttributeName = Html::getAttributeName($this->attribute);
            //get filename from selected file
            $imagesIdArr = [];
            $ImageManager_fileName = null;
            if ($mImageManager !== null && !$this->multiple) {
                $ImageManager_id = $this->model->{$sFieldAttributeName};
                $mImageManager = ImageManager::findOne($ImageManager_id);
                $ImageManager_fileName = $mImageManager->fileName;
            } else {
                $modelValue = $this->model->{$sFieldAttributeName};
                $imagesIdArr = explode(',', $modelValue);
                $ImageManager_id = $this->model->{$sFieldAttributeName};
            }
            //create field
            $field .= Html::textInput($this->attribute, $ImageManager_fileName, ['class' => 'form-control', 'id' => $sFieldNameId, 'readonly' => true]);
            $field .= Html::activeHiddenInput($this->model, $this->attribute, $this->options);
        } else {
            $field .= Html::textInput($this->name . "_name", null, ['readonly' => true]);
            $field .= Html::hiddenInput($this->name, $this->value, $this->options);
        }
        //end input group
        if (!$this->multiple) {
            $sHideClass = $ImageManager_id === null ? 'hide' : '';
            $field      .= "<a href='#' class='input-group-addon btn btn-primary delete-selected-image " . $sHideClass
                . "' data-input-id='" . $sFieldId . "' data-show-delete-confirm='"
                . ($this->showDeletePickedImageConfirm ? "true" : "false")
                . "'><i class='glyphicon glyphicon-remove' aria-hidden='true'></i></a>";
        }
        if ($this->multiple) {
            $field .= "<a href='#' class='input-group-addon btn btn-primary open-modal-imagemanager-multiple' data-aspect-ratio='"
                . $this->aspectRatio . "' data-crop-view-mode='" . $this->cropViewMode . "' data-input-id='" . $sFieldId
                . "'>";
            $field .= "<i class='glyphicon glyphicon-plus' aria-hidden='true'></i>";
        } else {
            $field .= "<a href='#' class='input-group-addon btn btn-primary open-modal-imagemanager' data-aspect-ratio='"
                . $this->aspectRatio . "' data-crop-view-mode='" . $this->cropViewMode . "' data-input-id='" . $sFieldId
                . "'>";
            $field .= "<i class='glyphicon glyphicon-folder-open' aria-hidden='true'></i>";
        }
        $field .= "</a></div>";

        //show preview if is true
        if ($this->showPreview == true) {
            if ($this->multiple) {
                $sImageSources = (!empty($imagesIdArr)) ? \Yii::$app->imagemanager->getImageArrPath(
                    $imagesIdArr,
                    500,
                    500,
                    'inset'
                ) : [];

                $field .= '<div class="image-wrapper image-wrapper__multiple" id="imageManagerImgHolder">';
                foreach ($sImageSources as $imageSource) {
                    $field .= '<div class="image-manager__multiple-item" data-id="' . $imageSource['id'] . '">
                            <button type="button" class="delete-image-btn">X</button>
                            <img alt="Thumbnail" class="img-responsive img-preview" src="' . $imageSource['url'] . '">
                        </div>';
                }
                $field .= '</div>';
            } else {
                $sHideClass = ($mImageManager == null) ? "hide" : "";
                $sImageSource = isset($mImageManager->id) ? \Yii::$app->imagemanager->getImagePath(
                    $mImageManager->id,
                    500,
                    500,
                    'inset'
                ) : "";

                if ($this->multiple) {
                    $field .= '<div class="image-wrapper image-wrapper__multiple ' . $sHideClass
                        . '" id="imageManagerImgHolder">';
                } else {
                    $field .= '<div class="image-wrapper ' . $sHideClass . '" id="imageManagerImgHolder">';
                    $field .= '<img id="' . $sFieldId
                        . '_image" alt="Thumbnail" class="img-responsive img-preview" src="' . $sImageSource . '">';
                }
                $field .= '</div>';
            }
        }

        //close image-manager-input div
        $field .= "</div>";

        echo $field;

        $this->registerClientScript();
    }

    /**
     * Registers js Input
     */
    public function registerClientScript() {
        $view = $this->getView();
        ImageManagerInputAsset::register($view);

        //set baseUrl from image manager
        $sBaseUrl = Url::to(['/imagemanager/manager']);
        //set base url
        $view->registerJs("imageManagerInput.multiple = " . (($this->multiple) ? 'true' : 'false') . ";");
        $view->registerJs("imageManagerInput.baseUrl = '" . $sBaseUrl . "';");
        $view->registerJs("imageManagerInput.message = " . Json::encode([
                    'imageManager' => Yii::t('imagemanager','Image manager'),
                    'detachWarningMessage' => Yii::t('imagemanager', 'Are you sure you want to detach the image?'),
                ]) . ";");
    }

}
