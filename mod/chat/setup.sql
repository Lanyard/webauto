
drop table if exists webauto_chat;
create table webauto_chat (
    user_id     MEDIUMINT NOT NULL,
    message     FLOAT NOT NULL,
    time        DATETIME NOT NULL,

    CONSTRAINT `webauto_chat_ibfk_1`
        FOREIGN KEY (`user_id`)
        REFERENCES `webauto_lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(user_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

