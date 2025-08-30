<?php
namespace Exports;

use Exports\Api\Adapter\ExportAdapter;
use Exports\Form\ModuleConfigForm;
use Exports\Job\ExportJob;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Module\AbstractModule;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include sprintf('%s/config/module.config.php', __DIR__);
    }

    public function install(ServiceLocatorInterface $services)
    {
        $sql = <<<'SQL'
CREATE TABLE exports_export (id INT UNSIGNED AUTO_INCREMENT NOT NULL, owner_id INT DEFAULT NULL, job_id INT DEFAULT NULL, type VARCHAR(255) NOT NULL, name VARCHAR(255) DEFAULT NULL, `label` VARCHAR(255) NOT NULL, data LONGTEXT NOT NULL COMMENT '(DC2Type:json)', created DATETIME NOT NULL, INDEX IDX_85ABAB067E3C61F9 (owner_id), INDEX IDX_85ABAB06BE04EA9 (job_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
ALTER TABLE exports_export ADD CONSTRAINT FK_85ABAB067E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL;
ALTER TABLE exports_export ADD CONSTRAINT FK_85ABAB06BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id) ON DELETE SET NULL;
SQL;
        $conn = $services->get('Omeka\Connection');
        $conn->exec('SET FOREIGN_KEY_CHECKS=0;');
        $conn->exec($sql);
        $conn->exec('SET FOREIGN_KEY_CHECKS=1;');

    }

    public function uninstall(ServiceLocatorInterface $services)
    {
        $conn = $services->get('Omeka\Connection');
        $conn->exec('SET FOREIGN_KEY_CHECKS=0;');
        $conn->exec('DROP TABLE IF EXISTS exports_export;');
        $conn->exec('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function getConfigForm(PhpRenderer $view)
    {
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        $form = $services->get('FormElementManager')->get(ModuleConfigForm::class);
        $form->setData([
            'exports_directory_path' => $settings->get('exports_directory_path'),
        ]);
        return $view->partial('common/exports-config-form', ['form' => $form]);
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        $form = $services->get('FormElementManager')->get(ModuleConfigForm::class);
        $form->setData($controller->params()->fromPost());
        if ($form->isValid()) {
            $formData = $form->getData();
            $settings->set('exports_directory_path', $formData['exports_directory_path']);
            return true;
        }
        $controller->messenger()->addErrors($form->getMessages());
        return false;
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        /*
         * Dispatch the export job and set the export name after the export is created.
         */
        $sharedEventManager->attach(
            ExportAdapter::class,
            'api.create.post',
            function (Event $event) {
                $services = $this->getServiceLocator();
                $dispatcher = $services->get('Omeka\Job\Dispatcher');
                $entityManager = $services->get('Omeka\EntityManager');
                $exportEntity = $event->getParam('response')->getContent();

                $exportJob = $dispatcher->dispatch(
                    ExportJob::class,
                    ['export_id' => $exportEntity->getId()]
                );
                // The export name is a union of the export type, the timestamp
                // when the job was started (to ensure uniqueness and consistent
                // file sorting), and a random string (to further ensure
                // uniqueness).
                $exportName = sprintf(
                    '%s_%s_%s',
                    $exportEntity->getType(),
                    $exportJob->getStarted()->format('U'),
                    substr(md5(rand()), 0, 4)
                );

                $exportEntity->setJob($exportJob);
                $exportEntity->setName($exportName);
                $entityManager->flush();
            }
        );
    }

    public static function exportsDirectoryPathIsValid(string $exportsDirectoryPath)
    {
        return (is_dir($exportsDirectoryPath) && is_writable($exportsDirectoryPath));
    }
}
