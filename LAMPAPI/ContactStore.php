<?php

include_once "Database.php";
include_once "model/Contact.php";

class ContactStore
{
    /**
     * @var Database $db;
     */
    private $db;
    const TABLE_NAME = "Contacts";

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Fetches the contacts associated with the given user ID.
     *
     * @param int $userID
     * @return false|array
     */
    public function getContactsForUser($userID)
    {
        $sql = $this->db->prepare("SELECT * FROM ".ContactStore::TABLE_NAME." WHERE USERID = ?");
        $sql->bind_param("i", $userID);
        $sql->execute();

        $result = $sql->get_result();
        if (!$result) {
            return false;
        }

       return $this->serializeContacts($result);
    }

    /**
     * @param mysqli_result $result
     * @return array
     */
    private function serializeContacts($result) {
        $contacts = array();

        while ($row = $result->fetch_assoc()) {
            $contact = Contact::fromArray($row);
            array_push($contacts, $contact);
        }

        return $contacts;
    }

    /**
     * Creates a new contact and adds it to the database.
     *
     * @param Contact $contact
     * @return false|int
     */
    public function createContact($contact)
    {
        $sql = $this->db->prepare("INSERT INTO ".ContactStore::TABLE_NAME." (FirstName, LastName, PhoneNumber, Address, City, State, ZIP, UserID) values (?, ?, ?, ?, ?, ?, ?, ?)");
        echo $this->db->getError();
        $sql->bind_param("sssssssi",
            $contact->firstName,
            $contact->lastName,
            $contact->phoneNumber,
            $contact->address,
            $contact->city,
            $contact->state,
            $contact->zip,
            $contact->userID
        );
        $sql->execute();

        if ($sql->affected_rows < 1) {
            return false;
        }

        return $sql->insert_id;
    }

    /**
     * Updates a contact
     *
     * @param Contact $contact
     * @return false|mysqli_result
     */
    public function updateContact($contact)
    {
        $sql = $this->db->prepare("UPDATE ".ContactStore::TABLE_NAME." SET FirstName=?, LastName=?, PhoneNumber=?, Address=?, City=?, State=?, ZIP=? WHERE ID=?");
        $sql->bind_param("sssssssi",
            $contact->firstName,
            $contact->lastName,
            $contact->phoneNumber,
            $contact->address,
            $contact->city,
            $contact->state,
            $contact->zip,
            $contact->id
        );
        $sql->execute();

        return $sql->get_result();
    }

    /**
     * Deletes a given contact
     *
     * @param int $id The contact ID to delete
     * @return false|mysqli_result
     */
    public function deleteContact($id)
    {
        $sql = $this->db->prepare("DELETE FROM ".ContactStore::TABLE_NAME." WHERE ID=?");
        $sql->bind_param("i", $id);
        $sql->execute();

        return $sql->get_result();
    }

    /**
     * Does a contact query from the given keywords.
     *
     * @param int $userID
     * @param string $keyword
     * @return array|false
     */
    public function searchContact($userID, $keyword) {
        $keyword = "%".$keyword."%";
        $userID = intval($userID);
        $sql = $this->db->prepare("SELECT * FROM ".ContactStore::TABLE_NAME." WHERE (UserID=? AND (FirstName    LIKE ? OR 
                                                                                                   LastName     LIKE ? OR 
                                                                                                   PhoneNumber  LIKE ? OR
                                                                                                   Address      LIKE ? OR
                                                                                                   City         LIKE ? OR
                                                                                                   State        LIKE ? OR
                                                                                                   ZIP          LIKE ? OR
                                                                                                   CONCAT(FirstName, ' ', LastName) LIKE ?))");
        $sql->bind_param("issssssss", $userID, $keyword, $keyword, $keyword, $keyword, $keyword, $keyword, $keyword, $keyword);
        $sql->execute();

        $result = $sql->get_result();
        if (!$result) {
            return false;
        }

        return $this->serializeContacts($result);
    }

    /**
     * Save profile image for contact
     *
     * @param $imageFilename string The contact image filename
     * @param $userID string The id of the contact.
     * @return false|mysqli_result
     */
    public function saveProfileImg($imageFilename, $userID) {
        $sql = $this->db->prepare("UPDATE ".ContactStore::TABLE_NAME." SET ProfileImage=? WHERE ID=?");
        $sql->bind_param("si", $imageFilename, $userID);
        $sql->execute();

        return $sql->get_result();
    }

}