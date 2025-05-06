<?php
declare(strict_types=1);

namespace classes\ui\author;

use assStackQuestion;
use Expand;
use ilCtrl;
use ilCtrlException;
use ILIAS\UI\Component\Input\Container\Form\Standard;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use ilassStackQuestionPlugin;

/**
 * This file is part of the STACK Question plugin for ILIAS, an advanced STEM assessment tool.
 * This plugin is developed and maintained by SURLABS and is a port of STACK Question for Moodle,
 * originally created by Chris Sangwin.
 *
 * The STACK Question plugin for ILIAS is open-source and licensed under GPL-3.0.
 * For license details, visit https://www.gnu.org/licenses/gpl-3.0.en.html.
 *
 * To report bugs or participate in discussions, visit the Mantis system and filter by
 * the category "STACK Question" at https://mantis.ilias.de.
 *
 * More information and source code are available at:
 * https://github.com/surlabs/STACK
 *
 * If you need support, please contact the maintainer of this software at:
 * stack@surlabs.es
 *
 *********************************************************************/
class ScoringUI
{
    private ilassStackQuestionPlugin $plugin;
    private Factory $factory;
    private Renderer $renderer;
    private ilCtrl $control;
    private assStackQuestion $question;
    private $request;

    /**
     * Constructor
     *
     * @param ilassStackQuestionPlugin $plugin
     * @param assStackQuestion $question The question object
     */
    public function __construct(ilassStackQuestionPlugin $plugin, assStackQuestion $question)
    {
        global $DIC;

        $this->plugin = $plugin;
        $this->question = $question;
        $this->factory = $DIC->ui()->factory();
        $this->renderer = $DIC->ui()->renderer();
        $this->control = $DIC->ctrl();
        $this->request = $DIC->http()->request();
    }

    /**
     * ### MAIN METHOD OF THIS CLASS ###
     * Creates and returns the scoring panel UI component.
     *
     * @return string
     * @throws ilCtrlException
     */
    public function getScoringPanelUIComponent(): string
    {
        $form = $this->renderForm();
        $table = $this->resultTable();

        return $form . $table;
    }


    /**
     * @throws ilCtrlException
     */
    public function renderForm(): string
    {
        $form = $this->buildForm();


        if ($this->request->getMethod() == "POST") {
            $form = $form->withRequest($this->request);
            $result = $form->getData();

            if ($result) {
                $this->save($result);
                $form = $this->buildForm();
            }
        }

        return $this->renderer->render($form);
    }

    /**
     * @throws ilCtrlException
     */
    public function buildForm(): Standard
    {
        $inputs = [
            "points" => $this->factory->input()->field()->numeric($this->plugin->txt("sco_current_scoring_form_input"), $this->plugin->txt("sco_current_scoring_info"))
                ->withValue($this->question->getPoints()),
        ];

        return $this->factory->input()->container()->form()->standard(
            $this->control->getLinkTargetByClass('assStackQuestionGUI', 'scoringManagementPanel'),
            [
                "scoring" => $this->factory->input()->field()->section($inputs, $this->plugin->txt("sco_scoring_form")),
            ]
        );
    }

    /**
     * @throws ilCtrlException
     */
    public function save(array $result): void
    {
        global $DIC;

        $this->question->setPoints($result["scoring"]["points"]);
        $this->question->saveToDb();

        // TODO Create confirm msg
        $DIC->ui()->mainTemplate()->setOnScreenMessage('success', $this->plugin->txt("sco_scoring_form_saved"), true);
        $DIC->ctrl()->redirectByClass('assStackQuestionGUI', 'scoringManagementPanel');
    }

    public function resultTable(): string
    {
        $rendered = '';
        $max_weight = 0.0;

        foreach ($this->question->prts as $prt) {
            if (is_a($prt, 'stack_potentialresponse_tree_lite')) {
                $max_weight += $prt->get_value();
            }
        }

        foreach ($this->question->prts as $prt) {
            $panel = $this->factory->panel()->standard($prt->get_name(), $this->factory->legacy(
            $this->plugin->txt("sco_prt_value") . " <strong>" . ($prt->get_value() / $max_weight) * $this->question->getPoints() . " </strong>"
            ))->withViewControls(array(
                new Expand(),
            ));

            $rendered .= $this->renderer->render($panel);
        }

        return $rendered;
    }

}