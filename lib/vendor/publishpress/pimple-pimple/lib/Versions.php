<?php


/*****************************************************************
 * This file is generated on composer update command by
 * a custom script. 
 * 
 * Do not edit it manually!
 ****************************************************************/
 
namespace PublishPress\PimplePimple;

if (! class_exists('PublishPress\\PimplePimple\\Versions')) {
    /**
     * Based on the ActionScheduler_Versions class from Action Scheduler library.
     */
    class Versions
    {
        /**
         * @var Versions
         */
        private static $instance = null;

        private $versions = array();

        public function register($versionString, $initializationCallback): bool
        {
            if (isset($this->versions[$versionString])) {
                return false;
            }

            $this->versions[$versionString] = $initializationCallback;

            return true;
        }

        public function getVersions(): array
        {
            return $this->versions;
        }

        public function latestVersion()
        {
            $keys = array_keys($this->versions);
            if (empty($keys)) {
                return false;
            }
            uasort($keys, 'version_compare');
            return end($keys);
        }

        public function latestVersionCallback()
        {
            $latest = $this->latestVersion();
            if (empty($latest) || ! isset($this->versions[$latest])) {
                return '__return_null';
            }

            return $this->versions[$latest];
        }

        /**
         * @return Versions
         * @codeCoverageIgnore
         */
        public static function getInstance(): ?Versions
        {
            if (empty(self::$instance)) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * @codeCoverageIgnore
         */
        public static function initializeLatestVersion(): void
        {
            $self = self::getInstance();

            call_user_func($self->latestVersionCallback());
        }
    }
}
