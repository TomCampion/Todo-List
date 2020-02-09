ToDoList
========

[![Codacy Badge](https://api.codacy.com/project/badge/Grade/c09c406a62164912b27ee2b9aa34ff5e)](https://app.codacy.com/manual/TomCampion/Todo-List?utm_source=github.com&utm_medium=referral&utm_content=TomCampion/Todo-List&utm_campaign=Badge_Grade_Dashboard)

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

