<?php

namespace app\components;

/**
*  Пробую написать свое первое нормальное поведение для yii2
*
*  @author Artem w1575 Agryzkov
*
*/


 //  Пример Вызова
 // 'helpersMany' => [
 //     'class'               => app\components\HasManyBehavior::className(),
 //     'junctionModel'       => SiteHelper::className(),
 //     'relatedTable'        => User::className(),
 //     'attribute'           => 'helpers',
 //     'thisKey'             => 'site',
 //     'relatedKey'          => 'user',
 // ],

use yii\base\Behavior;
use yii\db\ActiveRecord;
//use yii\db\Query;

class ManyToManyBehavior extends Behavior {

  /**
   * Первичный ключ текущей модели
   *
   * @var [type]
   */

  /**
   *
   * Таблица со списком связей
   *
   * @var [type]
   */
  public $junctionModel;

  /**
   *
   * @var [type]
   */
  public $thisKey;

  /**
   *
   * @var [type]
   */
  public $relatedKey;

  /**
   * Название таблицы со связями
   * @var [type]
   */
  public $relatedTable;

  /**
   * Это у нас тот самый аттрибут с помощью которого записывается или получается
   * список данных в связанной таблице
   *
   * @var [type]
   */
  public $attribute;

  /**
   *  Здесь у нас ничего необычного нет. Просто подрубаются к нужным ивентам
   *  нужные мне функции
   */

  /**
   * Здесь будут храниться подготовленные
   * @var [type]
   */
  protected $insertThis = [];

  /**
   * Здесь у нас будут храниться значения, которые нужно будет удалить
   *
   * @var [type]
   */
  protected $deleteThis = [];

  public function events()
  {
    return [
     ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeUpdate',
     ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
     ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate',
     ActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',

     // если вдруг в базе данных у нас не указаны ограничения
     // для внешних ключей то методы ниже будут делать грязную работу
     ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
     ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',

    ];
  }

  /**
   * Выполняем нужные нам действия до того, как у нас запишется новая модель
   * в базу данных
   *
   * @return [type] [description]
   */
  public function beforeInsert()
  {
    $this->insertThis = ($this->owner->{"_$this->attribute"} != 0 )
                        ? $this->owner->{"_$this->attribute"}
                        : []
    ;
    return false;
  }

  /**
   * Выполняем нужные нам действия до того, как даных в обновляемой модели
   * обновятся в базе
   * @return [type] [description]
   */
  public function beforeUpdate($insert)
  {
    $this->deleteThis = $this->owner->{$this->attribute};
    if($this->owner->{"_$this->attribute"} != 0)
      foreach($this->owner->{"_$this->attribute"} as $newKey => $newOne) {
        $isHere = false;
        foreach($this->owner->{$this->attribute} as $oldKey => $oldOne) {
          if($oldOne->primaryKey == $newOne) {
            $isHere = true;
            unset($this->deleteThis[$oldKey]);
            break;
          }
        }
        if($isHere!==true) {
          $this->insertThis[] = $newOne;
        }
      }
    return true;
  }



  public function beforeDelete()
  {
    $this->deleteThis = $this->owner->{$this->attribute};
  }

  public function afterDelete()
  {
    $this->deleteValues();
  }


   /**
    * Если у нас оснвные поля модели нормально обновились, то можно и делать
    * грязные дела по связанным моделям
    *
    * @return [type] [description]
    */
   public function afterInsert()
   {
     $this->insertValues();
     return false;
   }

   /**
    * После обновления данных в основной таблице
    *
    * @return [type] [description]
    */
   public function afterUpdate()
   {
     $this->deleteValues();
     $this->insertValues();
     return true;
   }

   /**
    * Удаляем ненужные значения
    * @return [type] [description]
    */
   private function deleteValues()
   {
     foreach($this->deleteThis as $one) {
       $findThis = $this->junctionModel::find()->where([
         $this->thisKey => $this->owner->primaryKey,
         $this->relatedKey => $one,
       ])->one();
       if(!is_null($findThis)) $findThis->delete();
       $findThis = null;
     }
     return true;
   }

   /**
    * Вставляем новые значения
    * @return [type] [description]
    */
   private function insertValues()
   {
     foreach($this->insertThis as $one ) {
      $newOne = new $this->junctionModel;
      $newOne->{$this->thisKey} = $this->owner->primaryKey;
      $newOne->{$this->relatedKey} = $one;
      // $newOne->key = $this->owner->primaryKey . $one;
      $newOne->save();
     }
     return true;
   }

}


?>
