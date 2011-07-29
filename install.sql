ALTER TABLE wcf1_user ADD likes MEDIUMINT(7) NOT NULL DEFAULT 0;

DROP TABLE IF EXISTS wcf1_like;
CREATE TABLE wcf1_like (
	likeID INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY, 
	objectID INT(10) NOT NULL DEFAULT 0,
	likeObjectTypeID INT(10) NOT NULL DEFAULT 0,
	objectUserID INT(10) NOT NULL DEFAULT 0,
	userID INT(10) NOT NULL DEFAULT 0,
	time INT(10) NOT NULL DEFAULT 0,
	UNIQUE KEY (likeObjectTypeID, objectID, userID),
	KEY (objectUserID),
	KEY (userID)
);

DROP TABLE IF EXISTS wcf1_like_object;
CREATE TABLE wcf1_like_object (
	likeObjectID INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	likeObjectTypeID INT(10) NOT NULL DEFAULT 0,
	objectID INT(10) NOT NULL DEFAULT 0, 
	objectUserID INT(10) NOT NULL DEFAULT 0,
	likes MEDIUMINT(7) NOT NULL DEFAULT 0,
	cachedUsers TEXT,
	UNIQUE KEY (likeObjectTypeID, objectID),
	KEY (objectUserID)
);

DROP TABLE IF EXISTS wcf1_like_object_type;
CREATE TABLE wcf1_like_object_type (
	likeObjectTypeID INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	objectTypeName VARCHAR(255) NOT NULL DEFAULT '',
	className VARCHAR(255) NOT NULL,
	packageID INT(10) NOT NULL DEFAULT 0,
	UNIQUE KEY (objectTypeName, packageID)
);

ALTER TABLE wcf1_like ADD FOREIGN KEY (likeObjectTypeID) REFERENCES wcf1_like_object_type (likeObjectTypeID) ON DELETE CASCADE;
ALTER TABLE wcf1_like ADD FOREIGN KEY (userID) REFERENCES wcf1_user (userID) ON DELETE CASCADE;

ALTER TABLE wcf1_like_object ADD FOREIGN KEY (likeObjectTypeID) REFERENCES wcf1_like_object_type (likeObjectTypeID) ON DELETE CASCADE;

ALTER TABLE wcf1_like_object_type ADD FOREIGN KEY (packageID) REFERENCES wcf1_package (packageID) ON DELETE CASCADE;