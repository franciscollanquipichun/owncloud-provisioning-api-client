<?php

namespace Arnovr\OwncloudProvisioning\Client;

use Arnovr\OwncloudProvisioning\Client\Command\AddUserToGroup;
use Arnovr\OwncloudProvisioning\Client\Command\CreateGroup;
use Arnovr\OwncloudProvisioning\Client\Command\CreateUser;
use Arnovr\OwncloudProvisioning\Client\Command\DeleteGroup;
use Arnovr\OwncloudProvisioning\Client\Command\DeleteUser;
use Arnovr\OwncloudProvisioning\Client\Command\DeleteUserFromGroup;
use Arnovr\OwncloudProvisioning\Client\Command\EditUser;
use Arnovr\OwncloudProvisioning\Client\Command\FindGroups;
use Arnovr\OwncloudProvisioning\Client\Command\FindGroupsOfUser;
use Arnovr\OwncloudProvisioning\Client\Command\FindSubAdminGroupsOfUser;
use Arnovr\OwncloudProvisioning\Client\Command\FindUser;
use Arnovr\OwncloudProvisioning\Client\Command\FindUsers;
use Arnovr\OwncloudProvisioning\Client\Command\FindUsersOfGroup;
use Arnovr\OwncloudProvisioning\Client\Command\MakeUserSubAdminOfGroup;
use Arnovr\OwncloudProvisioning\Client\Command\RemoveUsersSubAdminRightsFromGroup;
use Arnovr\OwncloudProvisioning\Client\ResponseParser\ResponseParser;
use Arnovr\OwncloudProvisioning\Client\Result\Exception\NothingToModifyException;
use Arnovr\OwncloudProvisioning\Client\Result\GroupsList;
use Arnovr\OwncloudProvisioning\Client\Result\User;
use Arnovr\OwncloudProvisioning\Client\Result\UserList;
use Arnovr\OwncloudProvisioning\Client\Result\StatusResult;
use Assert\Assertion;

class OwncloudClient
{
    /**
     * @var ApiConnection
     */
    private $apiConnection;

    /**
     * @var ResponseParser
     */
    private $responseParser;

    /**
     * OwncloudClient constructor.
     * @param ApiConnection $apiConnection
     * @param ResponseParser $responseParser
     */
    public function __construct(ApiConnection $apiConnection, ResponseParser $responseParser)
    {
        $this->apiConnection = $apiConnection;
        $this->responseParser = $responseParser;
    }

    /**
     * @param CreateUser $createUserCommand
     * @return StatusResult
     */
    public function createUser(CreateUser $createUserCommand)
    {
        $body = [
            'userid' => $createUserCommand->getUserName(),
            'password' => $createUserCommand->getPassword()
        ];
        $apiResponse = $this->apiConnection->sendRequest("POST", "/users", $this->buildFormParams($body));

        return $this->responseParser->parseResponse($apiResponse);
    }

    /**
     * @param EditUser $editUserCommand
     * @return StatusResult[]
     *
     * @throws NothingToModifyException
     */
    public function editUser(EditUser $editUserCommand)
    {
        $body = get_object_vars($editUserCommand);
        $putList = array();
        $index = 0;
        foreach($body as $key => $value)
        {
            if (!empty($value)) {
                $putList[$index]['key'] = $key;
                $putList[$index]['value'] = $value;
                $index++;
            }
        }

        if (count($body) === 0) {
            throw new NothingToModifyException('Nothing is specified to be modified');
        }
        $responseCollection = array();
        foreach($putList as $put)
        {
            $apiResponse = $this->apiConnection->sendRequest("PUT", "/users/" . $editUserCommand->userName, $this->buildFormParams($put));
            $responseCollection[$put['key']] = $this->responseParser->parseResponse($apiResponse);
        }
        return $responseCollection;
    }

    /**
     * @param DeleteUser $deleteUserCommand
     * @return StatusResult
     */
    public function deleteUser(DeleteUser $deleteUserCommand)
    {
        $apiResponse = $this->apiConnection->sendRequest("DELETE", "/users/" . $deleteUserCommand->getUserName());

        return $this->responseParser->parseResponse($apiResponse);
    }

    /**
     * @param FindUsers $findUsersCommand
     * @return UserList
     */
    public function findUsers(FindUsers $findUsersCommand)
    {
        $apiResponse = $this->apiConnection->sendRequest("GET", "/users?search=" . $findUsersCommand->getUserName());

        return $this->responseParser->parseFindUsers($apiResponse);
    }

    /**
     * @param FindUser $findUserCommand
     * @return User
     */
    public function findUser(FindUser $findUserCommand)
    {
        $apiResponse = $this->apiConnection->sendRequest("GET", "/users/" . $findUserCommand->userName);

        Assertion::same(100, (int) $apiResponse['statusCode']);

        return $this->responseParser->parseFindUser($apiResponse);
    }

    /**
     * @param AddUserToGroup $addUserToGroup
     * @return StatusResult
     */
    public function addUserToGroup(AddUserToGroup $addUserToGroup)
    {
        $body = [
            'groupid' => $addUserToGroup->getGroupId()
        ];

        $apiResponse = $this->apiConnection->sendRequest("POST", "/users/" . $addUserToGroup->getUserName() . '/groups', $this->buildFormParams($body));

        return $this->responseParser->parseResponse($apiResponse);
    }

    /**
     * @param FindGroupsOfUser $findGroupsOfUser
     * @return GroupsList
     */
    public function findGroupOfUsers(FindGroupsOfUser $findGroupsOfUser)
    {
        $apiResponse = $this->apiConnection->sendRequest("GET", "/users/" . $findGroupsOfUser->getUserName() . '/groups');

        return $this->responseParser->parseFindGroup($apiResponse);
    }

    /**
     * @param CreateGroup $createGroup
     * @return StatusResult
     */
    public function createGroup(CreateGroup $createGroup)
    {
        $body = [
            'groupid' => $createGroup->getGroupId()
        ];

        $apiResponse = $this->apiConnection->sendRequest("POST", "/groups", $this->buildFormParams($body));

        return $this->responseParser->parseResponse($apiResponse);
    }

    /**
     * @param DeleteGroup $deleteGroupCommand
     * @return StatusResult
     */
    public function deleteGroup(DeleteGroup $deleteGroupCommand)
    {
        $apiResponse = $this->apiConnection->sendRequest("DELETE", "/groups/" . $deleteGroupCommand->getGroupId());

        return $this->responseParser->parseResponse($apiResponse);
    }

    /**
     * @param DeleteUserFromGroup $deleteUserFromGroup
     * @return StatusResult
     */
    public function deleteUserFromGroup(DeleteUserFromGroup $deleteUserFromGroup)
    {
        $body = [
            'groupid' => $deleteUserFromGroup->getGroupId()
        ];
        $apiResponse = $this->apiConnection->sendRequest("DELETE", "/users/" . $deleteUserFromGroup->getUserName() . "/groups", $this->buildFormParams($body));
        return $this->responseParser->parseResponse($apiResponse);
    }


    /**
     * @param MakeUserSubAdminOfGroup $makeUserSubAdminOfGroup
     * @return StatusResult
     */
    public function makeUserSubAdminOfGroup(MakeUserSubAdminOfGroup $makeUserSubAdminOfGroup)
    {
        $body = [
            'groupid' => $makeUserSubAdminOfGroup->getGroupId()
        ];
        $apiResponse = $this->apiConnection->sendRequest("POST", "/users/" . $makeUserSubAdminOfGroup->getUserName() . "/subadmins", $this->buildFormParams($body));
        return $this->responseParser->parseResponse($apiResponse);
    }

    /**
     * @param RemoveUsersSubAdminRightsFromGroup $removeUsersSubAdminRightsFromGroup
     * @return StatusResult
     */
    public function removeUsersSubAdminRightsFromGroup(RemoveUsersSubAdminRightsFromGroup $removeUsersSubAdminRightsFromGroup)
    {
        $body = [
            'groupid' => $removeUsersSubAdminRightsFromGroup->getGroupId()
        ];
        $apiResponse = $this->apiConnection->sendRequest("DELETE", "/users/" . $removeUsersSubAdminRightsFromGroup->getUserName() . "/subadmins", $this->buildFormParams($body));
        return $this->responseParser->parseResponse($apiResponse);
    }

    /**
     * @param FindSubAdminGroupsOfUser $findSubAdminGroupsOfUser
     * @return GroupsList
     */
    public function findSubAdminGroupsOfUser(FindSubAdminGroupsOfUser $findSubAdminGroupsOfUser)
    {
        $apiResponse = $this->apiConnection->sendRequest("GET", "/users/" . $findSubAdminGroupsOfUser->getUserName() . "/subadmins");
        return $this->responseParser->parseFindGroup($apiResponse);
    }

    /**
     * @param FindGroups $findGroups
     * @return GroupsList
     */
    public function findGroups(FindGroups $findGroups)
    {
        $apiResponse = $this->apiConnection->sendRequest("GET", "/groups?search=" . $findGroups->getSearchGroup());
        return $this->responseParser->parseFindGroup($apiResponse);
    }

    /**
     * @param FindUsersOfGroup $findUsersOfGroup
     * @return UserList
     */
    public function findUsersOfGroup(FindUsersOfGroup $findUsersOfGroup)
    {
        $apiResponse = $this->apiConnection->sendRequest("GET", "/groups/" . $findUsersOfGroup->getGroupId());
        return $this->responseParser->parseFindUsers($apiResponse);
    }

    /**
     * @param array $body
     * @return array
     */
    private function buildFormParams(array $body)
    {
        return ['form_params' => $body];
    }
}
