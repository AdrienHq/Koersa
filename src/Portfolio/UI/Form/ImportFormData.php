<?php

declare(strict_types=1);

namespace Koersa\Portfolio\UI\Form;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ImportFormData
{
    public ?UploadedFile $file = null;
    public string $exchange = 'kraken';
}
