<?php
namespace Omnibox\Helper;

use Symfony\Component\Console\Question\Question;
use Omnibox\Model\Site;
use Omnibox\Helper\CliHelper;

class QuestionHelper
{
    /**
     * @var CliHelper
     */
    private $cliHelper;

    function __construct(CliHelper $cliHelper)
    {
        $this->cliHelper = $cliHelper;
    }

    public function promptSiteName($siteNames)
    {
        $questionHelper = $this->cliHelper->getHelperSet()->get('question');
        $input = $this->cliHelper->getInputInterface();
        $output = $this->cliHelper->getOutputInterface();

        $question = new Question('Assign a name for this project: ');
        $validator = $this;
        $question->setValidator(function ($answer) use ($siteNames, $validator) {
                return $validator->validateName($answer, $siteNames);
            });

        return $questionHelper->ask($input, $output, $question);
    }

    public function promptSiteDomain(Site $site, $domains)
    {
        $questionHelper = $this->cliHelper->getHelperSet()->get('question');
        $input = $this->cliHelper->getInputInterface();
        $output = $this->cliHelper->getOutputInterface();

        $question = new Question('Choose a domain: [www.'.$site->getName().'.dev] ', 'www.'.$site->getName().'.dev');
        $question->setAutocompleterValues(array(
                'www.'.$site->getName().'.dev',
                $site->getName().'.dev',
                'local.'.$site->getName().'.com',
                'www.'.$site->getName().'.com',
                $site->getName().'.com',
            ));
        $validator = $this;
        $question->setValidator(function ($answer) use ($domains, $validator) {
                return $validator->validateDomain($answer, $domains);
            });

        return $questionHelper->ask($input, $output, $question);
    }


    public function promptSiteDirectory(Site $site, $directories)
    {
        $questionHelper = $this->cliHelper->getHelperSet()->get('question');
        $input = $this->cliHelper->getInputInterface();
        $output = $this->cliHelper->getOutputInterface();

        $question = new Question('Choose a project directory - it will be created if it doesn\'t exist: ['.$_SERVER['HOME'] . DIRECTORY_SEPARATOR . 'Projects' . DIRECTORY_SEPARATOR . $site->getName().'] ', $_SERVER['HOME'] . DIRECTORY_SEPARATOR . 'Projects' . DIRECTORY_SEPARATOR . $site->getName());
        $question->setAutocompleterValues(array(
                $_SERVER['HOME'] . DIRECTORY_SEPARATOR . $site->getName(),
                $_SERVER['HOME'] . DIRECTORY_SEPARATOR . 'Projects' . DIRECTORY_SEPARATOR . $site->getName(),
            ));
        $validator = $this;
        $question->setValidator(
            function ($answer) use ($directories, $validator) {
                return $validator->validateDirectory($answer, $directories);
            }
        );

        return $questionHelper->ask($input, $output, $question);
    }

    public function promptSiteWebroot(Site $site)
    {
        $questionHelper = $this->cliHelper->getHelperSet()->get('question');
        $input = $this->cliHelper->getInputInterface();
        $output = $this->cliHelper->getOutputInterface();

        $question = new Question('Web root (relative to the site directory): [.] ', '.');
        $question->setValidator(
            function ($answer) use ($site) {
                if (!file_exists($site->getDirectory(). DIRECTORY_SEPARATOR . $answer)) {
                    throw new \RuntimeException(
                        'The folder does not exist. Try again.'
                    );
                }

                return $answer;
            }
        );

        return $questionHelper->ask($input, $output, $question);
    }

    public function promptSiteWebconfig(Site $site)
    {
        $questionHelper = $this->cliHelper->getHelperSet()->get('question');
        $input = $this->cliHelper->getInputInterface();
        $output = $this->cliHelper->getOutputInterface();

        $question = new Question('Server configuration (options: default, symfony, magento, wordpress): ['.$site->getWebconfig().'] ', $site->getWebconfig());
        $question->setValidator(
            function ($answer) use ($site) {
                if (!in_array($answer, array('default', 'symfony', 'magento', 'wordpress'))) {
                    throw new \RuntimeException(
                        'The configuration does not exist.'
                    );
                }

                return $answer;
            }
        );

        return $questionHelper->ask($input, $output, $question);
    }

    public function promptSiteServer(Site $site)
    {
        $questionHelper = $this->cliHelper->getHelperSet()->get('question');
        $input = $this->cliHelper->getInputInterface();
        $output = $this->cliHelper->getOutputInterface();

        $question = new Question('Web server (options: apache, nginx): ['.$site->getServer().'] ', $site->getServer());
        $question->setValidator(
            function ($answer) use ($site) {
                if (!in_array($answer, array('apache', 'nginx'))) {
                    throw new \RuntimeException(
                        'The configuration does not exist.'
                    );
                }

                return $answer;
            }
        );

        return $questionHelper->ask($input, $output, $question);
    }

    public function promptDistributionChoice($choices)
    {
        $questionHelper = $this->cliHelper->getHelperSet()->get('question');
        $input = $this->cliHelper->getInputInterface();
        $output = $this->cliHelper->getOutputInterface();

        $question = new Question('Choose your preferred Symfony flavor: [1] ', '1');
        $question->setValidator(
            function ($answer) use ($choices) {
                if (!array_key_exists($answer, $choices)) {
                    throw new \RuntimeException(
                        'Enter the choice number of your preferred Symfony distribution'
                    );
                }

                return $answer;
            }
        );

        return $questionHelper->ask($input, $output, $question);
    }

    public function validateDirectory($directory, $directories)
    {
        if (in_array($directory, $directories)) {
            throw new \RuntimeException(
                'There is already a site with this directory!'
            );
        } elseif (file_exists($directory)) {
            if (!(count(scandir($directory)) == 2)) {
//                Directory is not empty
//                throw new \RuntimeException(
//                    'This directory is not empty. Try again.'
//                );
            }
        }
        exec('su $SUDO_USER -c "mkdir -p '.$directory.'"');

        return $directory;
    }

    public function validateName($name, $siteNames)
    {
        if (strlen(trim($name)) === 0) {
            throw new \RuntimeException(
                'You need to provide a name for this site!'
            );
        } elseif (in_array($name, $siteNames)) {
            throw new \RuntimeException(
                'There is already a site with this name!'
            );
        }

        $name = str_replace(" ", "-", $name);
        $name = strtolower(preg_replace("/[^a-zA-Z0-9-]+/", "", $name));

        return $name;
    }

    public function validateDomain($domain, $domains)
    {
        if (!(preg_match('/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i', $domain) && //valid chars check
            preg_match('/^.{1,253}$/', $domain) && //overall length check
            preg_match('/^[^\.]{1,63}(\.[^\.]{1,63})*$/', $domain))) {
            throw new \RuntimeException(
                'This is not a valid domain name!'
            );
        } elseif (in_array($domain, $domains)) {
            throw new \RuntimeException(
                'There is already a site with this domain!'
            );
        }

        return $domain;
    }
}
