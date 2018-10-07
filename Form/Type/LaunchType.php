<?php

namespace Hallboav\DatainfoBundle\Form\Type;

use Hallboav\DatainfoBundle\Entity\LaunchData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class LaunchType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('activity_id', TextType::class)
            ->add('performed_tasks', CollectionType::class, [
                'entry_type' => PerformedTaskType::class,
                'allow_add' => true,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => LaunchData::class,
        ]);
    }
}
