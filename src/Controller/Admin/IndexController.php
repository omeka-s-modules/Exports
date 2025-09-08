<?php
namespace Exports\Controller\Admin;

use Exports\Exporter;
use Exports\Form\ExportForm;
use Exports\Form\ExporterForm;
use Exports\Job\DeleteExportJob;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Omeka\Form\ConfirmForm;
use Omeka\Stdlib\Message;

class IndexController extends AbstractActionController
{
    protected $exporterManager;

    public function __construct(Exporter\Manager $exporterManager)
    {
        $this->exporterManager = $exporterManager;
    }

    public function browseAction()
    {
        $this->setBrowseDefaults('created');
        $query = $this->params()->fromQuery();
        $response = $this->api()->search('exports_exports', $query);
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));
        $exports = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('exports', $exports);
        return $view;
    }

    public function setExporterAction()
    {
        $form = $this->getForm(ExporterForm::class);

        if ($this->getRequest()->isPost()) {
            $post = $this->params()->fromPost();
            $form->setData($post);
            if ($form->isValid()) {
                $this->messenger()->addSuccess('Configure your export below.'); // @translate
                return $this->redirect()->toRoute(null, ['action' => 'export', 'id' => null], ['query' => ['exporter_name' => $post['exporter_name']]], true);
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }

    public function exportAction()
    {
        $exporterName = $this->params()->fromQuery('exporter_name');
        $exporter = $this->exporterManager->get($exporterName);

        if ($exporter instanceof Exporter\Unknown) {
            // The exporter is unknown.
            return $this->redirect()->toRoute(null, ['action' => 'browse', 'id' => null], true);
        }

        $form = $this->getForm(ExportForm::class, [
            'exporter_name' => $exporterName,
        ]);
        $form->setData([
            'o:label' => sprintf('%s - %s', $exporter->getLabel(), date('c')),
        ]);

        if ($this->params('id')) {
            // Re-create an existing export if an ID is passed in the route. Get
            // the export and populate the form.
            $export = $this->api()->read('exports_exports', $this->params('id'))->getContent();
            $form->setData([
                'o-module-exports:export_data' => $export->data(),
            ]);
        }

        if ($this->getRequest()->isPost()) {
            $postData = $this->params()->fromPost();
            $form->setData($postData);
            if ($form->isValid()) {
                // Set the export data.
                $formData = $form->getData();
                // Create the export resource.
                $response = $this->api($form)->create('exports_exports', $formData);
                if ($response) {
                    $export = $response->getContent();
                    $exportJob = $export->job();
                    // Set the message and redirect to browse.
                    $message = new Message(
                        '%s <a href="%s">%s</a>',
                        $this->translate('Exporting. This may take a while.'),
                        htmlspecialchars($this->url()->fromRoute('admin/id', ['controller' => 'job', 'id' => $exportJob->id()])),
                        $this->translate('See this job for progress.')
                    );
                    $message->setEscapeHtml(false);
                    $this->messenger()->addSuccess($message);
                    return $this->redirect()->toRoute(null, ['action' => 'browse', 'id' => null], true);
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }

    public function deleteConfirmAction()
    {
        $export = $this->api()->read('exports_exports', $this->params('id'))->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('common/delete-confirm-details');
        $view->setVariable('resource', $export);
        $view->setVariable('resourceLabel', 'export'); // @translate
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $export = $this->api()->read('exports_exports', $this->params('id'))->getContent();
            $form = $this->getForm(ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api($form)->delete('exports_exports', $export->id());
                if ($response) {
                    // Dispatch the export delete job only if the job is at a
                    // state where it can be done safely.
                    $exportJob = $export->job();
                    if ($exportJob && in_array($exportJob->status(), ['completed', 'stopped', 'error'])) {
                        $deleteExportjob = $this->jobDispatcher()->dispatch(
                            DeleteExportJob::class,
                            ['export_name' => $export->name()]
                        );
                        $message = new Message(
                            '%s <a href="%s">%s</a>',
                            $this->translate('Successfully deleted the export resource. Deleting export artifacts from the server.'),
                            htmlspecialchars($this->url()->fromRoute('admin/id', ['controller' => 'job', 'id' => $deleteExportjob->getId()])),
                            $this->translate('See this job for progress.')
                        );
                        $message->setEscapeHtml(false);
                    } else {
                        $message = $this->translate('Successfully deleted the export resource. Could not delete export artifacts from the server.');
                    }
                    $this->messenger()->addSuccess($message);
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        return $this->redirect()->toRoute(null, ['action' => 'browse', 'id' => null], true);
    }

    public function showDetailsAction()
    {
        $export = $this->api()->read('exports_exports', $this->params('id'))->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('export', $export);
        return $view;
    }
}
