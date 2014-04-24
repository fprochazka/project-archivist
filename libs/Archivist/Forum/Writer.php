<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Forum;

use Archivist\Security\Role;
use Archivist\Security\UserContext;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Writer extends Nette\Object
{

	/**
	 * @var \Kdyby\Doctrine\EntityManager
	 */
	private $em;

	/**
	 * @var \Archivist\Security\UserContext
	 */
	private $user;



	public function __construct(Kdyby\Doctrine\EntityManager $em, UserContext $user)
	{
		$this->em = $em;
		$this->user = $user;
	}



	public function askQuestion(Question $question, Category $category)
	{
		$question->category = $category;
		$question->setAuthor($this->user->getIdentity());

		$this->em->persist($question)->flush();

		return $question;
	}



	public function answerQuestion(Answer $answer, Question $question)
	{
		$question->addAnswer($answer);
		$answer->setAuthor($this->user->getIdentity());

		$this->em->persist($answer)->flush();

		return $answer;
	}



	/**
	 * @param Question $question
	 * @param Answer $answer
	 * @return Question
	 */
	public function toggleResolvedBy(Question $question, Answer $answer)
	{
		if (!$question->isAuthor($this->user->getIdentity()) && !$this->user->isInRole(Role::MODERATOR)) {
			throw new ModificationsNotAllowedException();
		}

		if ($question->solution === $answer) {
			$question->solution = NULL;

		} else {
			$question->setSolution($answer);
		}

		return $question;
	}

}
