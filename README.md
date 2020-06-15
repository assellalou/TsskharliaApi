# :rainbow:Tsskharlia Project API made with :purple_heart:

A simple **API** based on one of our previous MVC projects to handle routes, mainly made for the graduation projects. made by [ASSELLALOU](https://github.com/assellalou) in participation of [Oualid](https://github.com/OualidEssaidi) and [Zineb](https://github.com/zayne-up) handling the frontend of the project
![GIF](https://media.giphy.com/media/6mkfj3LKowCe4/giphy.gif)

# Docs 

>This API is still in it's early dev versions so if it happen that you notice any error, bug or vulnurability please report it.
## Authorization :construction:
This version of the API uses **Bearer Token** for authorization based on **JWT**.  
Every user regardless of it's role have one of these tokens so that it makes it possible to operate on the data behind the API.  
The *Login* route returns one token everytime you login to your account with the right credentials and it lasts 100 minutes before it expires then you'll need a new one.

## Routing :carousel_horse:
The API has 9 routes till now they all require authorization except for the `login` and `register` routes, it also requires data to be **JSON** formated and sent throught **POST** and only that otherwise any requests will get weird error message is response :smiling_imp:
>check `routes.php` for all available routes  

**EXAMPLE** `/login`  
The login route for example accepts two parameters **email** and **password** 

    {
        "email":"example@domain.com",
        "password":"Qwerty123"
    }

And if the credentials are actually right you'll get a response similar to this  

    {
        "success": 1,
        "status": 200,
        "message": "You have successfuly logged in!",
        "data": {
            "token": "The_bearer_token",
            "user": {
                "UserID": "6",
                "FirstName": "Eva",
                "LastName": "Elfie",
                "Email": "evil@dev.com",
                "CurrentRole": "1",
                "JoinDate": "2020-06-19 06:06:09",
                "BirthDate": "2000-05-27",
                "Gender": "F",
                "Country": "RU",
                "Region": "Moscow Oblast",
                "City": "Moscow",
                "Street": "1st Northern Line",
                "Building": "lakimanka",
                "HouseNumber": "69",
                "ZipCode": "119180",
                "IsValid": "1",
                "ECoin": "69",
                "profilePic": "base64"
            }
    }
otherwise it will return  
>Error responses vary depending on what you have done wrong so don't expect the same error messages

    {
        "success": 0,
        "status": 422,
        "message": "Invalid Email/Password!",
        "data": []
    }

### spread love :heart: