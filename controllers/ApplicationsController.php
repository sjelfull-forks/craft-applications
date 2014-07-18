<?php
namespace Craft;

/**
 * Class ApplicationsController
 * @package Craft
 */
class ApplicationsController extends BaseController
{
    /**
     * @var Allows anonymous access to this controller's actions.
     * @access protected
     */
    protected $allowAnonymous = array('actionSubmit');

    // private $application;
    // private $plugin;
    //
    // public function init()
    // {
    //     $this->plugin = craft()->plugins->getPlugin('applications');
    //
    //     if (!$this->plugin)
    //     {
    //         throw new Exception('Couldn’t find the Applications plugin!');
    //     }
    // }


    /**
     * Render the application index
     */
    public function actionIndex()
    {
        $variables['forms'] = craft()->applications_forms->getAllForms();

        $this->renderTemplate('applications/_index', $variables);
    }

    /**
     * Allow public submission of the application
     */
    // public function actionSubmit()
    // {
    //     $this->requirePostRequest();
    //
    //     $this->application = new Applications_ApplicationModel();
    //
    //     // $settings = $this->plugin->getSettings();
    //
    //     $this->application->firstName = craft()->request->getPost('firstName');
    //     $this->application->lastName  = craft()->request->getPost('lastName');
    //     $this->application->email     = craft()->request->getPost('email');
    //     $this->application->phone     = craft()->request->getPost('phone');
    //
    //     if ($this->application->validate())
    //     {
    //         if (craft()->applications->publicSubmission($this->application)) {
    //             $this->redirectToPostedUrl($this->application);
    //         }
    //         else {
    //
    //         }
    //     }
    //     else
    //     {
    //         // $this->charge->addError('general', 'There was a problem with your details, please check the form and try again');
    //     }
    //
    //
    // }

    /**
     * Render a application form
     */
    public function actionNew(array $variables = array())
    {

        if (!empty($variables['formHandle']))
        {
            $variables['form'] = craft()->applications_forms->getFormByHandle($variables['formHandle']);
        }
        else if (!empty($variables['formId']))
        {
            $variables['form'] = craft()->applications_forms->getFormById($variables['formId']);
        }

        // Now let's set up the actual application
        if (empty($variables['application']))
        {
            if (!empty($variables['applicationId']))
            {
                $variables['application'] = craft()->applications->getApplicationById($variables['applicationId']);

                if (!$variables['application'])
                {
                    throw new HttpException(404);
                }
            }
            else
            {
                $variables['application'] = new Applications_ApplicationModel();
                $variables['application']->formId = $variables['form']->id;
            }
        }

        // Tabs
        $variables['tabs'] = array();

        foreach ($variables['form']->getFieldLayout()->getTabs() as $index => $tab)
        {
            // Do any of the fields on this tab have errors?
            $hasErrors = false;

            if ($variables['application']->hasErrors())
            {
                foreach ($tab->getFields() as $field)
                {
                    if ($variables['application']->getErrors($field->getField()->handle))
                    {
                        $hasErrors = true;
                        break;
                    }
                }
            }

            $variables['tabs'][] = array(
                'label' => $tab->name,
                'url'   => '#tab'.($index+1),
                'class' => ($hasErrors ? 'error' : null)
            );
        }

        // Set the "Continue Editing" URL
        $variables['continueEditingUrl'] = 'applications/'.$variables['form']->handle.'/{id}';

        // Set a list of the variables to use in the select dropdown.
        $variables['customStatuses'] = array(
            ApplicationStatus::Approved => 'approved',
            ApplicationStatus::Denied   => 'denied',
            ApplicationStatus::Pending  => 'pending',
        );

        $this->renderTemplate('applications/_new', $variables);
    }

    /**
     * Edit an application.
     *
     * @param array $variables
     * @throws HttpException
     */
    public function actionEdit(array $variables = array())
    {
        if (!empty($variables['formHandle']))
        {
            $variables['form'] = craft()->applications_forms->getFormByHandle($variables['formHandle']);
        }
        else if (!empty($variables['formId']))
        {
            $variables['form'] = craft()->applications_forms->getFormById($variables['formId']);
        }

        if (empty($variables['form']))
        {
            throw new HttpException(404);
        }

        // Now let's set up the actual application
        if (empty($variables['application']))
        {
            if (!empty($variables['applicationId']))
            {
                $variables['application'] = craft()->applications->getApplicationById($variables['applicationId']);

                if (!$variables['application'])
                {
                    throw new HttpException(404);
                }
            }
            else
            {
                $variables['application'] = new Applications_ApplicationModel();
                $variables['application']->formId = $variables['form']->id;
            }
        }

        // Tabs
        $variables['tabs'] = array();

        foreach ($variables['form']->getFieldLayout()->getTabs() as $index => $tab)
        {
            // Do any of the fields on this tab have errors?
            $hasErrors = false;

            if ($variables['application']->hasErrors())
            {
                foreach ($tab->getFields() as $field)
                {
                    if ($variables['application']->getErrors($field->getField()->handle))
                    {
                        $hasErrors = true;
                        break;
                    }
                }
            }

            $variables['tabs'][] = array(
                'label' => $tab->name,
                'url'   => '#tab'.($index+1),
                'class' => ($hasErrors ? 'error' : null)
            );
        }

        if (!$variables['application']->id)
        {
            $variables['title'] = Craft::t('Create a new application');
        }
        else
        {
            $variables['title'] = $variables['application']->title;
        }

        // Breadcrumbs
        $variables['crumbs'] = array(
            array(
                'label' => Craft::t('Applications'),
                'url' => UrlHelper::getUrl('applications'
            )),
            array(
                'label' => $variables['form']->name,
                'url' => UrlHelper::getUrl('applications')
            )
        );

        // Grab all notes related to this application
        // if (!empty($variables['notes']))
        // {
        //     $variables['notes'] = craft()->applications->getNotesByApplicationId($variables['applicationId']);
        // }

        // Set the "Continue Editing" URL
        $variables['continueEditingUrl'] = 'applications/'.$variables['form']->handle.'/{id}';

        // Set a list of the enums to use in the status select dropdown
        $variables['customStatuses'] = array(
            ApplicationStatus::Approved => 'approved',
            ApplicationStatus::Denied   => 'denied',
            ApplicationStatus::Pending  => 'pending',
        );

        // Render the template!
        $this->renderTemplate('applications/_edit', $variables);
    }

    /**
     * Saves an application.
     */
    public function actionSave()
    {
        $this->requirePostRequest();

        $applicationId = craft()->request->getPost('applicationId');

        if ($applicationId)
        {
            $application = craft()->applications->getApplicationById($applicationId);

            if (!$application)
            {
                throw new Exception(Craft::t('No application exists with the ID “{id}”', array(
                    'id' => $applicationId
                    )
                ));
            }
        }
        else
        {
            $application = new Applications_ApplicationModel();
        }

        // Set the application attributes, defaulting to the existing values for
        // whatever is missing from the post data
        $application->formId     = craft()->request->getPost('formId', $application->formId);
        $application->firstName  = craft()->request->getPost('firstName');
        $application->lastName   = craft()->request->getPost('lastName');
        $application->email      = craft()->request->getPost('email');
        $application->phone      = craft()->request->getPost('phone');
        $application->status     = craft()->request->getPost('status');
        // $application->submitDate = (($submitDate = craft()->request->getPost('submitDate')) ? DateTime::createFromString($submitDate, craft()->timezone) : null);

        $application->setContentFromPost('fields');

        if (craft()->applications->save($application))
        {
            craft()->userSession->setNotice(Craft::t('Application saved.'));
            $this->redirectToPostedUrl($application);
        }
        else
        {
            craft()->userSession->setError(Craft::t('Couldn’t save application.'));

            // Send the application back to the template
            craft()->urlManager->setRouteVariables(array(
                'application' => $application
            ));
        }
    }

    /**
     * Changes an application to approved.
     */
    public function actionApprove()
    {
        $this->requirePostRequest();

        $status  = ApplicationStatus::Approved;
        $applicationId = craft()->request->getRequiredPost('applicationId');

        if (craft()->applications->updateStatus($applicationId, $status))
        {
            craft()->userSession->setNotice(Craft::t('The application was {status}.', array(
                'status' => $status
            )));
            $this->redirectToPostedUrl();
        }
        else
        {
            craft()->userSession->setError(Craft::t('Unable to change the application to {status}.', array(
                'status' => $status
            )));
        }
    }

    /**
     * Changes an application to denied.
     */
    public function actionDeny()
    {
        $this->requirePostRequest();

        $status  = ApplicationStatus::Denied;
        $applicationId = craft()->request->getRequiredPost('applicationId');

        if (craft()->applications->updateStatus($applicationId, $status))
        {
            craft()->userSession->setNotice(Craft::t('The application was {status}.', array(
                'status' => $status
            )));
            $this->redirectToPostedUrl();
        }
        else
        {
            craft()->userSession->setError(Craft::t('Unable to change the application to {status}.', array(
                'status' => $status
            )));
        }
    }

    /**
     * Changes an application to pending.
     */
    public function actionPending()
    {
        $this->requirePostRequest();

        $status  = ApplicationStatus::Pending;
        $applicationId = craft()->request->getRequiredPost('applicationId');

        if (craft()->applications->updateStatus($applicationId, $status))
        {
            craft()->userSession->setNotice(Craft::t('The application was {status}.', array(
                'status' => $status
            )));
        }
        else
        {
            craft()->userSession->setError(Craft::t('Unable to change the application to {status}.', array(
                'status' => $status
            )));
        }
    }

    /**
     * Deletes an application.
     */
    public function actionDeleteApplication()
    {
        $this->requirePostRequest();

        $applicationId = craft()->request->getRequiredPost('applicationId');

        if (craft()->elements->deleteElementById($applicationId))
        {
            craft()->userSession->setNotice(Craft::t('Application deleted.'));
            $this->redirectToPostedUrl();
        }
        else
        {
            craft()->userSession->setError(Craft::t('Couldn’t delete application.'));
        }
    }
}
