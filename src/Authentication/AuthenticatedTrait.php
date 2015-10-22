<?php

namespace Fuzz\ApiServer\Authentication;

use Fuzz\User\Models\User;
use Fuzz\ApiServer\Exception\ForbiddenException;
use League\OAuth2\Server\Exception\InvalidRequestException;
use LucaDegasperi\OAuth2Server\Facades\Authorizer;

trait AuthenticatedTrait
{
	/**
	 * The authenticated user.
	 *
	 * @var User
	 */
	protected $user;

	/**
	 * Enforce the requirement of a valid access token.
	 *
	 * @throws \Fuzz\ApiServer\Exception\ForbiddenException
	 * @return User
	 */
	final protected function requireAuth()
	{
		if (! $this->authenticate()) {
			throw new ForbiddenException('You must be logged in.');
		}

		return $this->user;
	}

	/**
	 * Enforce the existence of a scope. This is stricter than requiring authentication.
	 *
	 * @param string $scope
	 * @throws \Fuzz\ApiServer\Exception\ForbiddenException
	 * @return void
	 */
	final protected function requireScope($scope)
	{
		if (! Authorizer::hasScope($scope)) {
			throw new ForbiddenException('You do not have the required scope.', compact('scope'));
		}
	}

	/**
	 * Suggest the requirement of a valid access token.
	 *
	 * @return User|boolean
	 */
	final protected function suggestAuth()
	{
		return $this->authenticate();
	}

	/**
	 * Read client headers and load the associated user.
	 *
	 * @return User|boolean
	 */
	final private function authenticate()
	{
		if (isset($this->user)) {
			return $this->user;
		}

		try {
			Authorizer::validateAccessToken();

			$user_model = config('auth.model');

			if (! is_a($user_model, 'Fuzz\User\Models\User', true)) {
				throw new \LogicException(
					'Default ' . $user_model . ' is not an instance of Fuzz\User\Models\User'
				);
			}

			return $this->user = $user_model::find(Authorizer::getResourceOwnerId());
		} catch (InvalidRequestException $e) {
			return false;
		}
	}
}
