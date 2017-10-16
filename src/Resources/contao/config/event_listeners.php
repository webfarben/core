<?php

/**
 * This file is part of MetaModels/core.
 *
 * (c) 2012-2017 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels
 * @subpackage Core
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Christopher Boelter <christopher@boelter.eu>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2017 The MetaModels team.
 * @license    https://github.com/MetaModels/core/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

use MetaModels\DcGeneral\Events\MetaModel\CreateVariantButton;
use MetaModels\DcGeneral\Events\MetaModel\CutButton;
use MetaModels\DcGeneral\Events\MetaModel\DuplicateModel;
use MetaModels\DcGeneral\Events\MetaModel\PasteButton;
use MetaModels\DcGeneral\Events\Table\FilterSetting\FilterSettingTypeRendererCore;
use MetaModels\DcGeneral\Events\Table\InputScreens\InputScreenAddAllHandler;
use MetaModels\DcGeneral\Events\Table\RenderSetting\RenderSettingAddAllHandler;
use MetaModels\Events\MetaModelsBootEvent;
use MetaModels\Events\ParseItemEvent;
use MetaModels\MetaModelsEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

return array(
    MetaModelsEvents::SUBSYSTEM_BOOT => array(
        function (MetaModelsBootEvent $event) {
            new DuplicateModel($event->getServiceContainer());
        }
    ),
    MetaModelsEvents::SUBSYSTEM_BOOT_FRONTEND => array(
        function (MetaModelsBootEvent $event) {
            $handler = new MetaModels\FrontendIntegration\Boot();
            $handler->perform($event);
        }
    ),
    MetaModelsEvents::SUBSYSTEM_BOOT_BACKEND => array(
        function (MetaModelsBootEvent $event) {
            /** @var EventDispatcherInterface $dispatcher */
            $dispatcher = func_get_arg(2);

            $handler = new MetaModels\BackendIntegration\Boot();
            $handler->perform($event);
            new FilterSettingTypeRendererCore($event->getServiceContainer());
            new PasteButton($event->getServiceContainer());
            new CutButton($event->getServiceContainer());
            new CreateVariantButton($event->getServiceContainer());
            $dispatcher->addSubscriber(new RenderSettingAddAllHandler($event->getServiceContainer()));
            $dispatcher->addSubscriber(new InputScreenAddAllHandler($event->getServiceContainer()));
        }
    ),
    // deprecated since 2.0, to be removed in 3.0.
    MetaModelsEvents::PARSE_ITEM => array(
        array(
            function (ParseItemEvent $event) {
                // HOOK: let third party extensions manipulate the generated data.
                if (empty($GLOBALS['METAMODEL_HOOKS']['MetaModelItem::parseValue'])
                    || !is_array($GLOBALS['METAMODEL_HOOKS']['MetaModelItem::parseValue'])
                ) {
                    return;
                }

                trigger_error(
                    'The HOOK MetaModelItem::parseValue has been replaced by the event ' .
                    MetaModelsEvents::PARSE_ITEM .
                    ' and will get removed in 3.0.',
                    E_USER_DEPRECATED
                );

                $result    = $event->getResult();
                $item      = $event->getItem();
                $format    = $event->getDesiredFormat();
                $settings  = $event->getRenderSettings();
                foreach ($GLOBALS['METAMODEL_HOOKS']['MetaModelItem::parseValue'] as $hook) {
                    $className = $hook[0];
                    $method    = $hook[1];

                    if (in_array('getInstance', get_class_methods($className))) {
                        $instance = call_user_func(array($className, 'getInstance'));
                    } else {
                        $instance = new $className();
                    }
                    $instance->$method($result, $item, $format, $settings);
                }

                $event->setResult($result);
            },
            -10
        )
    )
);
