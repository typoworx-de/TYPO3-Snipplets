<?php
declare(strict_types=1);
namespace Foo\Bar\ViewHelpers\Image\Traits;

use Psr\Http\Message\RequestInterface;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference as ExtbaseFileReference;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

trait ImageHelperTrait
{
    protected array $processingInstructions = [];

    protected ?ImageService $imageService = null;

    protected string $cropVariant;
    protected ?CropVariantCollection $cropVariantCollection = null;


    protected function initializeImageHelperTrait(): void
    {
        $this->imageService = GeneralUtility::makeInstance(ImageService::class);
    }

    protected function initializeImageViewHelperArguments(): void
    {
        $this->registerArgument(
            'src',
            'string',
            'a path to a file, a combined FAL identifier or an uid (int). If $treatIdAsReference is set, the integer is considered the uid of the sys_file_reference record. If you already got a FAL object, consider using the $image parameter instead',
            false,
            ''
        );
        $this->registerArgument('treatIdAsReference', 'bool', 'given src argument is a sys_file_reference record', false, false);
        $this->registerArgument('image', 'object', 'a FAL object (\\TYPO3\\CMS\\Core\\Resource\\File or \\TYPO3\\CMS\\Core\\Resource\\FileReference)');
        $this->registerArgument('crop', 'string|bool|array', 'overrule cropping of image (setting to FALSE disables the cropping set in FileReference)');
        $this->registerArgument('cropVariant', 'string', 'select a cropping variant, in case multiple croppings have been specified or stored in FileReference', false, 'default');
        $this->registerArgument('fileExtension', 'string', 'Custom file extension to use');

        $this->registerArgument(
            'width',
            'string',
            'width of the image. This can be a numeric value representing the fixed width of the image in pixels. But you can also perform simple calculations by adding "m" or "c" to the value. See imgResource.width for possible options.'
        );
        $this->registerArgument(
            'height',
            'string',
            'height of the image. This can be a numeric value representing the fixed height of the image in pixels. But you can also perform simple calculations by adding "m" or "c" to the value. See imgResource.width for possible options.'
        );
        $this->registerArgument('minWidth', 'int', 'minimum width of the image');
        $this->registerArgument('minHeight', 'int', 'minimum height of the image');
        $this->registerArgument('maxWidth', 'int', 'maximum width of the image');
        $this->registerArgument('maxHeight', 'int', 'maximum height of the image');
        $this->registerArgument('absolute', 'bool', 'Force absolute URL', false, false);
    }

    protected function imageFactory(FileInterface|FileReference|ExtbaseFileReference|string|int|null $image): FileInterface|string|null
    {
        $src = '';
        $fal = null;

        if ($image === null)
        {
            return null;
        }
        else
        {
            if (is_object($image))
            {
                $fal = $image;
            }
            else
            {
                if (is_string($image) || is_int($image) && !empty($image))
                {
                    $src = (string)$image;
                }
            }
        }

        return $this->imageService?->getImage($src, $fal, (bool)$this->arguments['treatIdAsReference']);
    }

    protected function validateProcessingArguments(array $arguments): void
    {
        if ((string)$arguments['fileExtension'] && !GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], (string)$arguments['fileExtension']))
        {
            throw new Exception(
                $this->getExceptionMessage(
                    'The extension ' . $arguments['fileExtension'] . ' is not specified in $GLOBALS[\'TYPO3_CONF_VARS\'][\'GFX\'][\'imagefile_ext\']'
                    . ' as a valid image file extension and can not be processed.',
                ),
                1618989190
            );
        }

        if (empty($arguments['src']) === empty($arguments['image']))
        {
            throw new Exception($this->getExceptionMessage('You must either specify a string src or a File object.'), 1382284106);
        }
    }

    protected function preProcessInstructions(ExtbaseFileReference|FileInterface|null $image): void
    {
        if ($image === null)
        {
            return;
        }

        $cropString = $this->arguments['crop'];
        if ($cropString === null && $image->hasProperty('crop') && $image->getProperty('crop'))
        {
            $cropString = $image->getProperty('crop');
        }

        // CropVariantCollection needs a string, but this VH could also receive an array
        if (is_array($cropString))
        {
            $cropString = json_encode($cropString);
        }

        $this->cropVariantCollection = CropVariantCollection::create((string)$cropString);
        $this->cropVariant = $this->arguments['cropVariant'] ? : 'default';

        $cropArea = $this->cropVariantCollection->getCropArea($this->cropVariant);

        $this->normalizeDimensionsFromCrop($image, $cropArea);

        $this->processingInstructions = [
            'width' => $this->arguments['width'],
            'height' => $this->arguments['height'],
            'minWidth' => $this->arguments['minWidth'],
            'minHeight' => $this->arguments['minHeight'],
            'maxWidth' => $this->arguments['maxWidth'],
            'maxHeight' => $this->arguments['maxHeight'],
            'crop' => $cropArea->isEmpty() ? null : $cropArea->makeAbsoluteBasedOnFile($image),
        ];

        if (!empty($this->arguments['fileExtension'] ?? ''))
        {
            $this->processingInstructions['fileExtension'] = $this->arguments['fileExtension'];
        }
    }

    protected function applyProcessingInstructions(ExtbaseFileReference|FileInterface|null $image): ProcessedFile|ExtbaseFileReference|FileInterface
    {
        if (empty($this->processingInstructions))
        {
            return $image;
        }

        // Process the image (apply crop, generate processed file)
        return $this->imageService->applyProcessingInstructions(
            $image,
            $this->processingInstructions
        );
    }

    private function normalizeDimensionsFromCrop(FileInterface $image, Area $cropArea): void
    {
        [$width, $wMod] = $this->parseDimension($this->arguments['width'] ?? $this->arguments['maxWidth']);
        [$height, $hMod] = $this->parseDimension($this->arguments['height'] ?? $this->arguments['maxHeight']);

        // Nothing to normalize if neither is given
        if ($width === null && $height === null)
        {
            return;
        }

        if ($this->arguments['cropVariant'])
        {
            // Check if we have image crop-variant (ignoring default preset)
            $cropArea = $this->cropVariantCollection->getCropArea($this->arguments['cropVariant']);

            if (!($cropArea->getWidth() > 1 && $cropArea->getHeight() > 1 && $cropArea->getOffsetTop() > 0 && $cropArea->getOffsetLeft() > 0))
            {
                return;
            }
        }

        $ratio = $this->getCropRatio($image, $cropArea);

        // If ratio cannot be determined → do nothing (avoid breaking rendering)
        if ($ratio === null || $ratio <= 0)
        {
            return;
        }

        // Only width set → calculate height
        if ($width !== null && $height === null)
        {
            $height = (int)round($width / $ratio);
        }

        // Only height set → calculate width
        if ($width === null && $height !== null)
        {
            $width = (int)round($height * $ratio);
        }

        // Both given → check mismatch and fix height (unless intentionally forced)
        if ($width !== null && $height !== null)
        {
            // avoid div by zero
            $givenRatio = $width / max(1, $height);

            if (abs($givenRatio - $ratio) > 0.02)
            {
                $height = (int)round($width / $ratio);
            }
        }

        // Write back final values with suffix preserved
        if ($width !== null)
        {
            $this->arguments['width'] = sprintf('%d%s', $width, $wMod);
        }

        if ($height !== null)
        {
            $this->arguments['height'] = sprintf('%d%s', $height, $hMod);
        }
    }

    private function getCropRatio(FileInterface $image, ?Area $cropArea): ?float
    {
        $imgW = (int)$image->getProperty('width');
        $imgH = (int)$image->getProperty('height');

        if ($imgW <= 0 || $imgH <= 0)
        {
            return null; // cannot compute ratio
        }

        if ($cropArea === null || $cropArea->isEmpty())
        {
            return $imgW / $imgH;
        }

        $absolute = $cropArea->makeAbsoluteBasedOnFile($image);

        $cropW = (int)$absolute->getWidth();
        $cropH = (int)$absolute->getHeight();

        if ($cropW <= 0 || $cropH <= 0)
        {
            return $imgW / $imgH; // fallback to original aspect ratio
        }

        return $cropW / $cropH;
    }

    private function parseDimension(string|float|null $value): array
    {
        if (empty($value))
        {
            return [null, null]; // [numericValue, modifier]
        }

        $value = trim((string)$value);

        if (preg_match('~^(\d+)([cm])?$~i', $value, $matches))
        {
            return [
                (int)$matches[1],
                $matches[2] ?? null // 'c', 'm' or null
            ];
        }

        return [null, null];
    }

    protected function getExceptionMessage(string $detailedMessage): string
    {
        /** @var RenderingContext $renderingContext */
        $renderingContext = $this->renderingContext;
        $request = $renderingContext->getRequest();

        if ($request instanceof RequestInterface)
        {
            $currentContentObject = $request->getAttribute('currentContentObject');
            if ($currentContentObject instanceof ContentObjectRenderer)
            {
                return sprintf('Unable to render image tag in "%s": %s', $currentContentObject->currentRecord, $detailedMessage);
            }
        }

        return "Unable to render image tag: $detailedMessage";
    }
}
