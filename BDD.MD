### BDD créer avec l'application dbdiagram.io.
```
    Table entreprise {
    id int [pk, increment]
    name varchar
    domain varchar
    createdAt datetime
    }

    Table team {
    id int [pk, increment]
    name varchar
    entrepriseId int [ref: > entreprise.id]
    }

    Table user {
    id int [pk, increment]
    email varchar
    password varchar
    displayName varchar
    role varchar // admin, user
    entrepriseId int [ref: > entreprise.id]
    teamId int [ref: > team.id]
    }

    Table room {
    id int [pk, increment]
    name varchar
    isTemporary bool
    creatorId int [ref: > user.id]
    entrepriseId int [ref: > entreprise.id]
    createdAt datetime
    templateId int [ref: > room_template.id]
    }

    Table module {
    id int [pk, increment]
    name varchar
    code varchar // chat, video, files, etc.
    description text
    }

    Table module_room {
    id int [pk, increment]
    roomId int [ref: > room.id]
    moduleId int [ref: > module.id]
    config_json text
    }

    Table team_room_permission {
    id int [pk, increment]
    roomId int [ref: > room.id]
    teamId int [ref: > team.id]
    role varchar // viewer, editor, owner
    }

    Table message {
    id int [pk, increment]
    roomId int [ref: > room.id]
    userId int [ref: > user.id]
    content text
    createdAt datetime
    }

    Table file_resource {
    id int [pk, increment]
    roomId int [ref: > room.id]
    fileName varchar
    fileUrl varchar
    type varchar
    size int
    createdAt datetime
    }

    Table task {
    id int [pk, increment]
    roomId int [ref: > room.id]
    title varchar
    description text
    status varchar // colonne kanban
    position int
    assignedToUserId int [ref: > user.id]
    createdAt datetime
    }

    Table calendar_event {
    id int [pk, increment]
    roomId int [ref: > room.id]
    userId int [ref: > user.id]
    title varchar
    startDate datetime
    endDate datetime
    description text
    }

    Table document {
    id int [pk, increment]
    roomId int [ref: > room.id]
    content_markdown text
    updatedAt datetime
    }

    Table room_template {
    id int [pk, increment]
    name varchar
    description text
    }

    Table template_module {
    id int [pk, increment]
    templateId int [ref: > room_template.id]
    moduleId int [ref: > module.id]
    }
```