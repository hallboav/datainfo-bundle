<?php

namespace Hallboav\DatainfoBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Hallboav\DatainfoBundle\DependencyInjection\DatainfoExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class DatainfoBundle extends Bundle
{
    /**
     * Obtém a extensão deste bundle.
     *
     * @return ExtensionInterface
     */
    public function getContainerExtension(): ExtensionInterface
    {
        return new DatainfoExtension();
    }
}
