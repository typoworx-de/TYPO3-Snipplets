<?php
declare(strict_types=1);
namespace Foo\Bar\ViewHelpers\Image;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileInterface;
use Foo\Bar\ViewHelpers\Image\Traits\ImageHelperTrait;

class UriViewHelper extends AbstractViewHelper
{
    use ImageHelperTrait;


    public function initializeArguments() : void
    {
        parent::initializeArguments();
        $this->initializeImageViewHelperArguments();

        $this->registerArgument('as', 'mixed', 'Provide as variable');
    }

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

        if ($this->arguments['as'] ?? null)
        {
            $as = $this->arguments['as'];
            $this->templateVariableContainer->remove($as);
            $this->templateVariableContainer->add($as, []);

            $returnRenderedChildren = $this->renderChildren();
            if ($returnRenderedChildren !== null)
            {
                $this->templateVariableContainer->remove($as);

                return $returnRenderedChildren;
            }
        }

        return $imageUri;
    }
}
