DROP DATABASE IF EXISTS TssakharliaProj;
CREATE DATABASE
IF NOT EXISTS TssakharliaProj;
USE TssakharliaProj;
CREATE TABLE Roles
(
    RoleID INT NOT NULL AUTO_INCREMENT,
    Role VARCHAR(128) NOT NULL,
    RoleDesc VARCHAR(128) DEFAULT NULL,
    PRIMARY KEY(RoleID)
);

CREATE TABLE Users
(
    UserID INT NOT NULL AUTO_INCREMENT,
    FirstName VARCHAR(25) NOT NULL,
    LastName VARCHAR(25) NOT NULL,
    Email VARCHAR(100) NOT NULL,
    Phone VARCHAR(15),
    Pwd VARCHAR(256) NOT NULL,
    CurrentRole INT DEFAULT 1,
    IDNumber VARCHAR(8) UNIQUE,
    JoinDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    BirthYear INT,
    BirthMonth INT,
    BirthDay INT,
    Gender VARCHAR(3) DEFAULT 'N/A',
    Country VARCHAR(25) DEFAULT 'Morocco',
    Region VARCHAR(50),
    City VARCHAR(50),
    Street VARCHAR(50),
    Building VARCHAR(50),
    HouseNumber VARCHAR(50),
    ZipCode VARCHAR(15),
    Active BIT DEFAULT FALSE,
    LastCheck TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    IsValid BIT DEFAULT FALSE,
    ResetToken varchar(256),
    Latitude FLOAT,
    Longitude FLOAT,
    ECoin FLOAT DEFAULT 0,
    Deleted BOOLEAN DEFAULT 0,
    DeletionDate DATETIME DEFAULT NULL,
    PRIMARY KEY(UserID),
    FOREIGN KEY(CurrentRole) REFERENCES Roles(RoleID)
);

CREATE TABLE Orders
(
    OrderID INT NOT NULL AUTO_INCREMENT,
    Consumer INT NOT NULL,
    Deliveryman INT,
    Provider INT NOT NULL,
    Status VARCHAR(25) DEFAULT 'Published',
    Public BOOLEAN DEFAULT 1,
    Amount FLOAT NOT NULL,
    Tax FLOAT NOT NULL,
    Weight FLOAT NOT NULL,
    City VARCHAR(50) NOT NULL,
    Street VARCHAR(50) NOT NULL,
    Building VARCHAR(50) NOT NULL,
    HouseNumber VARCHAR(50) NOT NULL,
    Published TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Deleted BOOLEAN DEFAULT 0,
    DeletionDate DATETIME DEFAULT NULL,
    PRIMARY KEY(OrderID),
    FOREIGN KEY(Consumer) REFERENCES Users(UserID),
    FOREIGN KEY(Deliveryman) REFERENCES Users(UserID),
    FOREIGN KEY(Provider) REFERENCES Users(UserID)
);

CREATE TABLE Stock
(
    ItemID INT NOT NULL AUTO_INCREMENT,
    ItemName VARCHAR(100) NOT NULL,
    ItemWeight FLOAT NOT NULL,
    ItemPrice FLOAT NOT NULL,
    ItemProvider INT NOT NULL,
    ItemQuantity INT NOT NULL,
    PRIMARY KEY(ItemID),
    FOREIGN KEY(ItemProvider) REFERENCES Users(UserID)
);

CREATE TABLE OrderedSupplies
(
    OrderNumber INT NOT NULL AUTO_INCREMENT,
    ItemNumber INT NOT NULL,
    Quantity FLOAT DEFAULT 1,
    Deleted BOOLEAN DEFAULT 0,
    PRIMARY KEY(OrderNumber, ItemNumber),
    FOREIGN KEY(OrderNumber) REFERENCES Orders(OrderID),
    FOREIGN KEY(ItemNumber) REFERENCES Stock(ItemID)

);

CREATE TABLE Rates
(
    Deliveryman INT NOT NULL AUTO_INCREMENT,
    Consumer INT NOT NULL,
    Stars INT NOT NULL,
    Comment VARCHAR(250),
    PRIMARY KEY(Deliveryman, Consumer),
    FOREIGN KEY(Deliveryman) REFERENCES Users(UserID),
    FOREIGN KEY(Consumer) REFERENCES Users(UserID)
);

CREATE TABLE Matche
(
    OrderID INT NOT NULL AUTO_INCREMENT,
    Deliveryman INT DEFAULT 0,
    Consumer INT DEFAULT 0,
    MatchTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(OrderID),
    FOREIGN KEY(OrderID) REFERENCES Orders(OrderID),
    FOREIGN KEY(Deliveryman) REFERENCES Users(UserID),
    FOREIGN KEY(Consumer) REFERENCES Users(UserID)

);


CREATE TABLE EcoinTransfers
(
    TransferID INT NOT NULL AUTO_INCREMENT,
    Sender INT NOT NULL,
    Receiver INT NOT NULL,
    LinkedOrder INT NOT NULL,
    Amount FLOAT NOT NULL,
    PRIMARY KEY(TransferID),
    FOREIGN KEY(Sender) REFERENCES Users(UserID),
    FOREIGN KEY(Receiver) REFERENCES Users(UserID),
    FOREIGN KEY(LinkedOrder) REFERENCES Orders(OrderID)
);

CREATE TABLE EcoinPurchase
(
    PurchaseID INT NOT NULL AUTO_INCREMENT,
    Buyer INT NOT NULL,
    Amount FLOAT NOT NULL,
    PurchasedAt TIMESTAMP Default CURRENT_TIMESTAMP,
    PRIMARY KEY(PurchaseID),
    FOREIGN KEY(Buyer) REFERENCES Users(UserID)
);

CREATE TABLE Notifications
(
    NotifID INT NOT NULL AUTO_INCREMENT,
    ToNotify INT NOT NULL,
    NotifText VARCHAR(255),
    NotifLink VARCHAR(255) DEFAULT '#',
    Seen BOOLEAN DEFAULT 0,
    PRIMARY KEY(NotifID),
    FOREIGN KEY(ToNotify) REFERENCES Users(UserID)
);

INSERT INTO Roles(Role) 
    VALUES('Consumer'),
        ('Delivery Agent'),
        ('Provider'),
        ('Support');

Insert INTO Users(FirstName, LastName, Email, Pwd, CurrentRole)
    VALUES('Pizza','Hot', 'support@pizzahot.com', '$2y$10$xgVd6g7n5VW/4wPpwoRv9e2LxzaiY4ihtdJt0SmZoAP2OLgM/PbnW', 3),
        ('Twin','Tacos', 'food@twintacos.com', '$2y$10$xgVd6g7n5VW/4wPpwoRv9e2LxzaiY4ihtdJt0SmZoAP2OLgM/PbnW', 3),
        ('Test', 'Delivery', 'delivery@dev.com', '$2y$10$xgVd6g7n5VW/4wPpwoRv9e2LxzaiY4ihtdJt0SmZoAP2OLgM/PbnW', 2),
        ('Test', 'Consumer', 'consumer@dev.com', '$2y$10$xgVd6g7n5VW/4wPpwoRv9e2LxzaiY4ihtdJt0SmZoAP2OLgM/PbnW', 1),
        ('Support', 'Team', 'support@devwave.com', '$2y$10$xgVd6g7n5VW/4wPpwoRv9e2LxzaiY4ihtdJt0SmZoAP2OLgM/PbnW', 4);

INSERT INTO Stock(ItemName, ItemWeight, ItemPrice, ItemProvider, ItemQuantity)
    VALUES('Vegan pizza Meduim', 840, 99, 1, 99999),
        ('Margharita Meduim', 700, 72, 1, 99999),
        ('Chicken XLARGE', 270, 45, 2, 99999),
        ('Chawarma Meduim', 180, 35, 2, 99999);
