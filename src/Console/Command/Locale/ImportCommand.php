<?php

declare(strict_types=1);

namespace App\Console\Command\Locale;

use App\Console\Command\CommandAbstract;
use App\Environment;
use App\Locale;
use Gettext\Translation;
use Gettext\Translations;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'locale:import',
    description: 'Convert translated locale files into PHP arrays.',
)]
class ImportCommand extends CommandAbstract
{
    public function __construct(
        protected Environment $environment
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Import Locales');

        $locales = Locale::SUPPORTED_LOCALES;
        $locale_base = $this->environment->getBaseDirectory() . '/resources/locale';

        $jsTranslations = [];

        foreach ($locales as $locale_key => $locale_name) {
            if ($locale_key === Locale::DEFAULT_LOCALE) {
                continue;
            }

            $locale_source = $locale_base . '/' . $locale_key . '/LC_MESSAGES/default.po';

            if (is_file($locale_source)) {
                $translations = Translations::fromPoFile($locale_source);

                // Temporary inclusion of frontend translations
                $frontendTranslations = $locale_base . '/' . $locale_key . '/LC_MESSAGES/frontend.po';
                if (is_file($frontendTranslations)) {
                    $frontendTranslations = Translations::fromPoFile($frontendTranslations);
                    $translations->mergeWith($frontendTranslations);
                }

                $locale_dest = $locale_base . '/compiled/' . $locale_key . '.php';
                $translations->toPhpArrayFile($locale_dest);

                $localeJsKey = str_replace('.UTF-8', '', $locale_key);

                /** @var Translation $translation */
                foreach ($translations as $translation) {
                    if ($translation->isDisabled() || !$translation->hasTranslation()) {
                        continue;
                    }

                    if ($translation->hasPlural()) {
                        $string = [
                            $translation->getTranslation(),
                        ];

                        $pluralStrings = $translation->getPluralTranslations();
                        if (count($pluralStrings) > 0) {
                            $string = array_merge($string, $pluralStrings);
                        }
                    } else {
                        $string = $translation->getTranslation();
                    }

                    $jsTranslations[$localeJsKey][$translation->getOriginal()] = $string;
                }

                ksort($jsTranslations[$localeJsKey]);

                $io->writeln(__('Imported locale: %s', $locale_key . ' (' . $locale_name . ')'));
            }
        }

        $jsTranslations = json_encode(
            $jsTranslations,
            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );
        $jsTranslationsPath = $locale_base . '/translations.json';

        file_put_contents($jsTranslationsPath, $jsTranslations);

        $io->success('Locales imported.');
        return 0;
    }
}
