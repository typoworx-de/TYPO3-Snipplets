<?php
declare(strict_types=1);
namespace Foo\Bar\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileInterface;
use Foo\Bar\ViewHelpers\Image\Traits\ImageHelperTrait;

class ImageViewHelper extends AbstractTagBasedViewHelper
{
    use ImageHelperTrait;

    protected $tagName = 'img';


    public function render() : FileInterface|string|null
    {
        $this->validateProcessingArguments($this->arguments);

        $this->initializeImageHelperTrait();

        $image = $this->imageFactory($this->arguments['image'] ?? $this->arguments['src'] ?? null);
        $this->preProcessInstructions($image);

        try
        {
            $processedImage = $this->applyProcessingInstructions($image);
            $imageUri = $this->imageService->getImageUri($processedImage, $this->arguments['absolute']);

            if (!$this->tag->hasAttribute('data-focus-area'))
            {
                $focusArea = $this->cropVariantCollection?->getFocusArea($this->cropVariant);
                if (!$focusArea->isEmpty())
                {
                    $this->tag->addAttribute('data-focus-area', $focusArea->makeAbsoluteBasedOnFile($image));
                }
            }

            $this->tag->addAttribute('src', $imageUri);
            $this->tag->addAttribute('width', $processedImage->getProperty('width'));
            $this->tag->addAttribute('height', $processedImage->getProperty('height'));

            if (is_string($this->arguments['alt'] ?? false) && $this->arguments['alt'] === '')
            {
                // In case the "alt" attribute is explicitly set to an empty string, respect
                // this to allow excluding it from screen readers, improving accessibility.
                $this->tag->addAttribute('alt', '');
            }
            elseif (empty($this->arguments['alt']))
            {
                // The alt-attribute is mandatory to have valid html-code, therefore use "alternative" property or empty
                $this->tag->addAttribute('alt', $image->hasProperty('alternative') ? $image->getProperty('alternative') : '');
            }
            // Add title-attribute from property if not already set and the property is not an empty string
            $title = (string)($image->hasProperty('title') ? $image->getProperty('title') : '');
            if (empty($this->arguments['title']) && $title !== '')
            {
                $this->tag->addAttribute('title', $title);
            }
        }
        catch (ResourceDoesNotExistException $e)
        {
            // thrown if file does not exist
            throw new Exception($this->getExceptionMessage($e->getMessage()), 1509741911, $e);
        }
        catch (\UnexpectedValueException $e)
        {
            // thrown if a file has been replaced with a folder
            throw new Exception($this->getExceptionMessage($e->getMessage()), 1509741912, $e);
        }
        catch (\InvalidArgumentException $e)
        {
            // thrown if file storage does not exist
            throw new Exception($this->getExceptionMessage($e->getMessage()), 1509741914, $e);
        }

        return $this->tag->render();
    }
}
