<?php

namespace PublishPressInstanceProtection;

/**
 * Check if the plugin is on the standard path.
 */
class InstanceChecker
{
    const STATE_STYLE_ENQUEUED = 'styleEnqueued';

    private $pluginSlug;

    private $pluginName;

    private $pluginFolder;

    /**
     * @var bool
     */
    private $isProPlugin;

    /**
     * @var string
     */
    private $freePluginName;

    /**
     * Undocumented function
     *
     * @param Config $config
     */
    public function __construct($config)
    {
        $this->pluginSlug = $config->pluginSlug;
        $this->pluginName = $config->pluginName;
        $this->pluginFolder = $config->pluginFolder;
        $this->isProPlugin = $config->isProPlugin;
        $this->freePluginName = $config->freePluginName;

        if (! $this->isProPlugin) {
            $this->freePluginName = $this->pluginName;
        }

        if (empty($this->pluginFolder)) {
            $this->pluginFolder = $this->pluginSlug;
        }

        if (
            is_admin()
            && ! wp_doing_ajax()
            && ! wp_doing_cron()
        ) {
            add_action('admin_init', [$this, 'init'], $this->isProPlugin ? 7 : 5);
        }
    }

    public function getVersion()
    {
        return '1.0.3';
    }

    public function init()
    {
        global $pagenow;

        if ($pagenow !== 'plugins.php') {
            return;
        }

        if (! $this->getStateDuplicatedPluginCheck()) {
            $this->checkDuplicatedPluginsAndLatestVersions();

            $this->setFlagDuplicatedPluginCheck();
        }

        if ($this->getStateHasMultiplePluginsActivated()) {
            $this->addMultipleInstancesNotice();
        }

        if ($this->isProPlugin && $this->getStateFreePluginLoadedByItself()) {
            $this->addFreePluginNotice();
        }

        // This should run once per request.
        if (! $this->getStateStyleEnqueued()) {
            $this->addPluginsPageStyle();
            $this->setFlagStyleEnqueued();
        }

        if ($this->getStateHasMultiplePaths()) {
            // This should run for every instance
            add_action('after_plugin_row', [$this, 'addLatestVersionCheck'], 10, 2);
            add_action('after_plugin_row', [$this, 'addNonStandardPathCheck'], 10, 2);
        }
    }

    private function checkDuplicatedPluginsAndLatestVersions()
    {
        if (! function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();

        $pluginFiles = [];
        $pluginInstances = [];
        $latestVersions = [];

        foreach ($plugins as $pluginFile => $pluginData) {
            if ($this->pluginName === $pluginData['Name']) {
                $pluginFiles[] = $pluginFile;

                if (is_plugin_active($pluginFile)) {
                    $pluginInstances[] = $pluginFile;

                    if ($pluginData['Name'] === $this->freePluginName) {
                        $this->setStateFreePluginLoadedByItself();
                    }
                }

                if (! isset($latestVersions[$this->pluginSlug])) {
                    $latestVersions[$this->pluginSlug] = $pluginData['Version'];
                    continue;
                }

                if (version_compare($pluginData['Version'], $latestVersions[$this->pluginSlug], '>')) {
                    $latestVersions[$this->pluginSlug] = $pluginData['Version'];
                }
            }
        }

        if (count($pluginFiles) > 1) {
            $this->setStateHasMultiplePaths();
        }

        if (count($pluginInstances) > 1) {
            $this->setStateHasMultiplePluginsActivated();
        }

        $this->setStateLatestVersions($latestVersions);
    }

    public function addPluginsPageStyle()
    {
        add_action('admin_enqueue_scripts', function() {
            wp_add_inline_style(
                'wp-admin',
                '
                tr.ppa-plugin-warning {
                    background: #fff;
                }

                tr.ppa-plugin-warning td {
                    box-shadow: inset 0 -1px 0 rgb(0 0 0 / 10%);
                    overflow: hidden;
                    padding: 0;
                }

                tr.ppa-plugin-warning td > div {
                    margin: 5px 20px 15px 44px;
                }

                tr.ppa-plugin-warning td > div.multiple-instances-warning {
                    background-color: #ffc6c6;
                    border: 1px solid #edb977;
                    border-left: 4px solid #e1a04e;
                    padding-left: 6px;
                }

                tr.ppa-plugin-warning td > div.multiple-instances-warning .dashicons {
                    margin-right: 6px;
                    vertical-align: bottom;
                    color: #c18d17;
                }

                tr.ppa-plugin-warning td > div.multiple-instances-warning p {
                    margin: 0.5em 0;
                }

                tr.active + tr.ppa-plugin-warning td {
                    background-color: #f0f6fc;
                }
                '
            );
        });
    }

    public function addLatestVersionCheck($pluginFile, $pluginData)
    {
        if ($pluginData['Name'] !== $this->pluginName) {
            return;
        }

        if ($this->getStateVersionChecked($pluginFile)) {
            return;
        }

        $latestVersions = $this->getStateLatestVersions();

        if (! isset($latestVersions[$this->pluginSlug])) {
            return;
        }

        if (version_compare($pluginData['Version'], $latestVersions[$this->pluginSlug], '<')) {
            ?>
            <tr class="ppa-plugin-warning">
                <td colspan="4" class="colspanchange">
                    <div class="multiple-instances-warning">
                        <p>
                            <span class="dashicons dashicons-warning"></span>
                            <?php echo esc_html__('This plugin is outdated. You already have a more recent version installed. Please remove this version.', 'publishpress-instance-protection'); ?>
                        </p>
                    </div>
                </td>
            </tr>
            <?php
        }

        $this->setStateVersionChecked($pluginFile);
    }

    public function addNonStandardPathCheck($pluginFile, $pluginData)
    {
        if ($pluginData['Name'] !== $this->pluginName) {
            return;
        }

        if ($this->getStatePathHasBeenCheckedForPluginFile($pluginFile)) {
            return;
        }

        $expectedPath = $this->pluginFolder . '/' . $this->pluginSlug . '.php';

        if ($pluginFile !== $expectedPath) {
            ?>
            <tr class="ppa-plugin-warning">
                <td colspan="4" class="colspanchange">
                    <div class="multiple-instances-warning">
                        <p>
                            <span class="dashicons dashicons-warning"></span>
                            <?php echo sprintf(
                               esc_html__('This plugin is not installed in the standard folder. The current path is %1$s but it is expected to be %2$s.', 'publishpress-instance-protection'),
                                '<code>' . esc_html($pluginFile) . '</code>',
                                '<code>' . esc_html($expectedPath) . '</code>'
                            );
                            ?>
                        </p>
                    </div>
                </td>
            </tr>
            <?php
        }

        $this->setStatePathHasBeenCheckedForPluginFile($pluginFile);
    }

    public function addMultipleInstancesNotice()
    {
        if ($this->getStateMultipleInstancesNoticeAdded()) {
            return;
        }

        $pluginName = $this->pluginName;

        add_action('admin_notices', function() use ($pluginName) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo sprintf(esc_html__('You have activated multiple instances of %s. Please keep only one activated and remove the others.', 'publishpress-instance-protection'), esc_html($pluginName)); ?></p>
            </div>
            <?php
        });

        $this->setStateMultipleInstancesNoticeAdded();
    }

    public function addFreePluginNotice()
    {
        if (LibState::getPluginState($this->pluginSlug, 'freePluginNoticeAdded')) {
            return;
        }

        $pluginName = $this->pluginName;
        $freePluginName = $this->freePluginName;

        add_action('admin_notices', function() use ($pluginName, $freePluginName) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo sprintf(esc_html__('Please deactivate %1$s when %2$s is activated.', 'publishpress-instance-protection'), esc_html($freePluginName), esc_html($pluginName)); ?></p>
            </div>
            <?php
        });

        LibState::setPluginState($this->pluginSlug, 'freePluginNoticeAdded');
    }

    private function getStateHasMultiplePaths()
    {
        return (bool) LibState::getPluginState($this->pluginSlug, 'hasMultiplePaths');
    }

    private function setStateHasMultiplePaths()
    {
        LibState::setPluginState($this->pluginSlug, 'hasMultiplePaths');
    }

    private function getStateHasMultiplePluginsActivated()
    {
        return (bool) LibState::getPluginState($this->pluginSlug, 'hasMultipleInstances');
    }

    private function setStateHasMultiplePluginsActivated()
    {
        LibState::setPluginState($this->pluginSlug, 'hasMultipleInstances');
    }

    private function getStateFreePluginLoadedByItself()
    {
        return (bool) LibState::getState('freePluginIsLoaded' . $this->freePluginName);
    }

    private function setStateFreePluginLoadedByItself()
    {
        LibState::setState('freePluginIsLoaded' . $this->freePluginName);
    }

    private function setStatePathHasBeenCheckedForPluginFile($pluginFile)
    {
        LibState::setPluginState($this->pluginSlug, 'pathCheck' . $pluginFile);
    }

    private function getStatePathHasBeenCheckedForPluginFile($pluginFile)
    {
        return (bool) LibState::getPluginState($this->pluginSlug, 'pathCheck' . $pluginFile);
    }

    private function setStateVersionChecked($pluginFile)
    {
        LibState::setPluginState($this->pluginSlug, 'versionCheck' . $pluginFile);
    }

    private function getStateVersionChecked($pluginFile)
    {
        return (bool) LibState::getPluginState($this->pluginSlug, 'versionCheck' . $pluginFile);
    }

    private function getStateDuplicatedPluginCheck()
    {
        return (bool) LibState::getPluginState($this->pluginSlug, 'duplicatedPluginsCheck');
    }

    private function setFlagDuplicatedPluginCheck()
    {
        LibState::setPluginState($this->pluginSlug, 'duplicatedPluginsCheck');
    }

    private function getStateStyleEnqueued()
    {
        return (bool) LibState::getState(self::STATE_STYLE_ENQUEUED);
    }

    private function setFlagStyleEnqueued()
    {
        LibState::setState(self::STATE_STYLE_ENQUEUED);
    }

    private function setStateLatestVersions($latestVersions)
    {
        LibState::setPluginState($this->pluginSlug, 'latestVersions', $latestVersions);
    }

    private function getStateLatestVersions()
    {
        return (array) LibState::getPluginState($this->pluginSlug, 'latestVersions');
    }

    private function setStateMultipleInstancesNoticeAdded()
    {
        LibState::setPluginState($this->pluginSlug, 'multipleInstancesNoticeAdded');
    }

    private function getStateMultipleInstancesNoticeAdded()
    {
        return (bool) LibState::getPluginState($this->pluginSlug, 'multipleInstancesNoticeAdded');
    }
}
