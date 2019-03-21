<?php

namespace Hallboav\DatainfoBundle\Form\Type;

use Hallboav\DatainfoBundle\Entity\PerformedTaskData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class PerformedTaskType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'input' => 'datetime',
                'format' => 'yyyy-MM-dd\'T00:00:00\'xxx',
            ])
            ->add('start_time', DateTimeType::class, [
                'widget' => 'single_text',
                'input' => 'datetime',
                'format' => 'yyyy-MM-dd\'T\'HH:mm:ssxxx',
            ])
            ->add('end_time', DateTimeType::class, [
                'widget' => 'single_text',
                'input' => 'datetime',
                'format' => 'yyyy-MM-dd\'T\'HH:mm:ssxxx',
            ])
            ->add('description', TextType::class)
            ->add('ticket', TextType::class);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PerformedTaskData::class,
        ]);
    }
}
