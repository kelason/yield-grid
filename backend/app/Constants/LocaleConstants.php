<?php

declare(strict_types=1);

namespace App\Constants;

final class LocaleConstants
{
    public const DEFAULT = 'en';

    public const TAGALOG = 'tl';

    public const CEBUANO = 'ceb';

    /** @var list<string> */
    public const SUPPORTED = [self::DEFAULT, self::TAGALOG, self::CEBUANO];
}
