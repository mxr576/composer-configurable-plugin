<?php

namespace cweagans\Composer\Tests;

class ConfigurablePluginTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultValues()
    {
        $pluginStub = new PluginStub();
        $pluginStub->setConfiguration([
            'stringVal' => [
                'type' => 'string',
                'default' => 'asdf',
            ],
            'intVal' => [
                'type' => 'int',
                'default' => 123,
            ],
            'boolVal' => [
                'type' => 'bool',
                'default' => true,
            ],
            'listVal' => [
                'type' => 'list',
                'default' => ['asdf'],
            ],
        ]);
        $pluginStub->configure([], '');

        $this->assertEquals('asdf', $pluginStub->getConfig('stringVal'));
        $this->assertEquals(123, $pluginStub->getConfig('intVal'));
        $this->assertTrue($pluginStub->getConfig('boolVal'));
        $this->assertEquals(['asdf'], $pluginStub->getConfig('listVal'));
    }

    /**
     * @dataProvider getEnvvarDataProvider
     */
    public function testGetEnvvarName($given, $expected)
    {
        $pluginStub = new PluginStub();
        $pluginStub->configure([], 'test-package');
        $envvar = $pluginStub->getEnvvarName($given);

        $this->assertEquals($expected, $envvar);
    }

    public function getEnvvarDataProvider()
    {
        return [
            ['a-config-key', "TEST_PACKAGE_A_CONFIG_KEY"],
            ['another--key', "TEST_PACKAGE_ANOTHER__KEY"],
            ['aNOtHer--KeY', "TEST_PACKAGE_ANOTHER__KEY"],
        ];
    }

    /**
     * @dataProvider castEnvvarToBoolDataProvider
     */
    public function testCastEnvvarToBool($input, $expected)
    {
        $plugin = new PluginStub();
        $plugin->configure([], '');
        // No typehint = we can just throw junk data to the function for the
        // second arg here.
        $result = $plugin->castEnvvarToBool($input, 'fake');
        $this->assertEquals($expected, $result);
    }
    /**
     * Test as many cases for boolean string parsing as possible.
     */
    public function castEnvvarToBoolDataProvider()
    {
        return [
            ['FALSE', FALSE],
            ['False', FALSE],
            ['FaLsE', FALSE],
            ['false', FALSE],
            ['NO', FALSE],
            ['No', FALSE],
            ['no', FALSE],
            ['0', FALSE],
            ['TRUE', TRUE],
            ['True', TRUE],
            ['TrUe', TRUE],
            ['true', TRUE],
            ['YES', TRUE],
            ['Yes', TRUE],
            ['yes', TRUE],
            ['1', TRUE],
            // This is a special case for the test. Since we're passing 'fake'
            // as the second param, any case that doesn't result in a bool
            // should result in the string 'fake'.
            ['asdf', 'fake'],
        ];
    }

    /**
     * @dataProvider castEnvvarToListDataProvider
     */
    public function testCastEnvvarToList($input, $expected)
    {
        $plugin = new PluginStub();
        $plugin->configure([], '');
        // No typehint = we can just throw junk data to the function for the
        // second arg here.
        $result = $plugin->castEnvvarToList($input, ['fake']);
        $this->assertEquals($expected, $result);
    }
    /**
     * Test as many cases for envvar -> array parsing as possible.
     */
    public function castEnvvarToListDataProvider()
    {
        return [
            [
                'project/someproject',
                ['project/someproject']
            ],
            [
                'project/someproject,another/project',
                ['project/someproject','another/project']
            ],
            // This is a special case for the test. Since we're passing 'fake'
            // as the second param, any case that doesn't result in a bool
            // should result in the string 'fake'.
            [
                ',',
                ['fake'],
            ]
        ];
    }

    /**
     * @expectedException \LogicException
     */
    public function testUnconfigured()
    {
        $plugin = new PLuginStub();
        $plugin->getConfig('bad-key');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidConfigKey()
    {
        $plugin = new PluginStub();
        $plugin->configure([], '');
        $plugin->setConfiguration([]);
        $plugin->getConfig('bad-key');
    }

    public function testConfigInheritance()
    {
        $plugin = new PluginStub();
        $plugin->configure([], 'test');
        $plugin->setConfiguration([
            'test-key' => [
                'type' => 'string',
                'default' => 'asdf',
            ],
        ]);

        // Config from defaults.
        $plugin->getConfig('test-key');
        $this->assertEquals('asdf', $plugin->getConfig('test-key'));

        $plugin->configure([
            'test' => [
                'test-key' => 'jkl',
            ],
        ], 'test');

        // Config from composer.json.
        $this->assertEquals('jkl', $plugin->getConfig('test-key'));

        // Config from environment.
        putenv("TEST_TEST_KEY=qwerty");
        $this->assertEquals('qwerty', $plugin->getConfig('test-key'));
    }





}



class PluginStub
{
    use \cweagans\Composer\ConfigurablePlugin;

    public function setConfiguration(array $configuration) {
        $this->configuration = $configuration;
    }
}