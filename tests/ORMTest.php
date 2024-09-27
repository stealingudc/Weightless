<?php

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Weightless\Core\Exception\InvalidClassNameException;
use Weightless\Core\ORM;
use Weightless\Core\ORM\AutoIncrement;
use Weightless\Core\ORM\Column;
use Weightless\Core\ORM\Database;
use Weightless\Core\ORM\ID;
use Weightless\Core\ORM\QueryBuilder;
use Weightless\Core\ORM\Relationships\ManyToOne;
use Weightless\Core\ORM\Relationships\OneToMany;
use Weightless\Core\ORM\Table;
use Weightless\Core\ORM\Type;
use Weightless\Core\ORM\Validator;

// #[Table("test_model_table")]
// class TestModel
// {
//   #[ID]
//   #[Column("id")]
//   #[AutoIncrement]
//   public int $id;

//   #[Column("name")]
//   #[Type("required|string")]
//   public string $name;

//   #[OneToMany(TestModel2::class, "testModel")]
//   public array $testmodel2s;
// }

// #[Table("test_model_table_2")]
// class TestModel2 {
//   #[ID]
//   #[Column("id")]
//   #[AutoIncrement]
//   public int $id;

//   #[Column("value")]
//   #[Type("required|integer")]
//   public int $value;

//   #[ManyToOne(TestModel::class, "test_model_table")]
//   public TestModel $testModel;
// }

#[Table("_test_users")]
class User
{
  #[ID]
  #[AutoIncrement]
  #[Column(name: 'id')]
  public ?int $id = null;

  #[Column(name: 'username')]
  public string $username;

  #[OneToMany(targetEntity: Post::class, mappedBy: 'userId')]
  public array $posts = [];

  #[Column("coolness")]
  public float $coolnessFactor = 1;
}

#[Table("_test_posts")]
class Post
{
  #[ID]
  #[AutoIncrement]
  #[Column(name: 'id')]
  public ?int $id = null;

  #[Type("required|string")]
  #[Column(name: 'title')]
  public string $title;

  #[Type("required|integer")]
  #[Column(name: 'user_id')]
  public ?int $userId = null;

  public ?User $user = null;

  #[Column("visible")]
  public bool $visible = false;
}

#[Table("_test_empty")]
class EmptyModel
{
  #[ID]
  #[AutoIncrement]
  #[Column(name: 'id')]
  public ?int $id = null;
}

#[Table("_test_mixed")]
class MixedModel 
{
  #[ID]
  #[AutoIncrement]
  #[Column(name: 'id')]
  public ?int $id = null;

  #[Column("mixed")]
  public mixed $mixed;
}

class ORMTest extends TestCase
{
  private User $_user;
  private Post $_post;

  public function setUp(): void
  {
    $this->_user = new User();
    $this->_user->username = "TestUserName";
    $this->_post = new Post();
    $this->_post->title = "TestPostTitle";
  }

  #[Test]
  public function crudOperations()
  {
    ORM::save($this->_user);
    $this->_post->userId = $this->_user->id;
    ORM::save($this->_post);

    $user = ORM::find(User::class, "username", "TestUserName");
    $this->assertNotCount(0, $user);

    $post = ORM::find(Post::class, "title", "TestPostTitle");
    $this->assertNotCount(0, $post);
    $this->assertNotNull($this->_post->userId);

    $this->_user->username = "UpdatedTestUserName";
    ORM::update($this->_user);

    $this->assertNotCount(0, ORM::findAll(User::class));

    ORM::delete($this->_user);
    ORM::delete($this->_post);
  }

  #[Test]
  public function invalidIdDelete()
  {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Entity does not have a valid ID.");
    $this->_user->id = null;
    ORM::delete($this->_user);
  }

  #[Test]
  public function invalidTableName()
  {
    $this->expectException(InvalidClassNameException::class);
    ORM::find("NonExistingModel", "id", "1");
  }

  #[Test]
  public function invalidTableAttribute()
  {
    $this->expectException(\Exception::class);
    $class = new class {};
    ORM::find($class::class, "id", "1");
  }

  #[Test]
  public function findEmpty()
  {
    $emptyModel = new EmptyModel();
    // Creates the table
    ORM::save($emptyModel);
    ORM::delete($emptyModel);

    $emptyResult = ORM::find(EmptyModel::class, "id", 1);
    $this->assertCount(0, $emptyResult);
  }

  #[Test]
  public function queryBuilder()
  {
    ORM::save($this->_user);
    $this->_post->userId = $this->_user->id;
    ORM::save($this->_post);

    $res = QueryBuilder::table("_test_users")->where("id", "=", 1)->get();
    $this->assertNotNull($res); 
  }

  #[Test]
  public function validator(){
    $val = new Validator();

    $val->string("field", 5);
    $val->integer("field", "string");
    $val->required("field", null);

    $this->assertNotCount(0, $val->getErrors());
  }

  #[Test]
  public function findException(){
    $this->expectException(InvalidClassNameException::class);
    ORM::find("ORM_ClassThatDoesntExist", "id", "1");
  }

  #[Test]
  public function findAllException(){
    $this->expectException(InvalidClassNameException::class);
    ORM::findAll("ORM_ClassThatDoesntExist", "id", "1");
  }

  #[Test]
  public function findRelatedException(){
    $this->expectException(InvalidClassNameException::class);
    $refl = new ReflectionClass(ORM::class);
    $findRelated = $refl->getMethod("findRelated");
    $findRelated->setAccessible(true);

    $findRelated->invoke(null, "ORM_ClassThatDoesntExist", "id", new stdClass());
  }

  #[Test]
  public function handleUnsupportedDatatype(){
    $mixedModel = new MixedModel();
    $mixedModel->mixed = "value";

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Unsupported data type");

    ORM::save($mixedModel);
  }

  #[Test]
  public function getTableNameException(){
    $this->expectException(InvalidClassNameException::class);
    $refl = new ReflectionClass(ORM::class);
    $getTableName = $refl->getMethod("getTableName");
    $getTableName->setAccessible(true);

    $getTableName->invoke(null, "ORM_ClassThatDoesntExist");
  }

  public function tearDown(): void
  {
    Database::getConnection()->query("DROP TABLE IF EXISTS _test_users;")->execute();
    Database::getConnection()->query("DROP TABLE IF EXISTS _test_posts;")->execute();
  }
}
