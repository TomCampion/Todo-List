ToDoList
========

Todo List is an application allowing you to manage your daily tasks easily !

## Installation
1 - Clone or download the GitHub repository in the desired folder:
```
    git clone https://github.com/TomCampion/Todo-List
```
2 - Install libraries by running : 
```
    composer install
```


4 - Configure your environment variables in the .env file

5 - Create the database, if it does not already exist, type the command below :
```
    php bin/console doctrine:database:create
```
6 - Create the different tables in the database by applying the migrations :
```
    php bin/console doctrine:migrations:migrate
```

