<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'resource_not_found' => 'Ресурс не найден',
    'error_occured' => 'Произошла ошибка:',
    'refresh' => 'Обновить',
    'no_valid_otp' => 'В этом QR-коде нет допустимых OTP ресурсов',
    'something_wrong_with_server' => 'Что-то не так с вашим сервером',
    'Unable_to_decrypt_uri' => 'Не удалось расшифровать uri',
    'not_a_supported_otp_type' => 'Этот формат OTP в настоящее время не поддерживается',
    'cannot_create_otp_without_secret' => 'Невозможно создать OTP без секретного ключа',
    'data_of_qrcode_is_not_valid_URI' => 'Данные этого QR-кода не являются допустимым OTP Auth URI. QR-код содержит:',
    'wrong_current_password' => 'Неверный текущий пароль, ничего не было изменено',
    'error_during_encryption' => 'Сбой шифрования, ваша база данных остается незащищенной.',
    'error_during_decryption' => 'Сбой расшифровки, ваша база данных по-прежнему защищена. В основном, это вызвано проблемой целостности шифрованных данных для одной или нескольких учётных записей.',
    'qrcode_cannot_be_read' => 'Этот QR-код нечитаем',
    'too_many_ids' => 'в параметр запроса было включено слишком много идентификаторов, разрешено максимум 100',
    'delete_user_setting_only' => 'Могут быть удалены только настройки, созданные пользователем',
    'indecipherable' => '*неразборчиво*',
    'cannot_decipher_secret' => 'Секретный ключ не может быть расшифрован. В основном это может быть вызвано неправильным APP_KEY в .env файле 2Fauth или поврежденными данными, хранящимися в базе данных.',
    'https_required' => 'Требуется HTTPS контекст',
    'browser_does_not_support_webauthn' => 'Ваше устройство не поддерживает webauth. Повторите попытку позже, используя более современный браузер',
    'aborted_by_user' => 'Прервано пользователем',
    'security_device_already_registered' => 'Устройство уже зарегистрировано',
    'not_allowed_operation' => 'Операция не разрешена',
    'no_authenticator_support_specified_algorithms' => 'Ни один аутентификатор не поддерживает указанные алгоритмы',
    'authenticator_missing_discoverable_credential_support' => 'В аутентификаторе отсутствует обнаруживаемая поддержка учетных данных',
    'authenticator_missing_user_verification_support' => 'В аутентификаторе отсутствует поддержка проверки пользователя',
    'unknown_error' => 'Неизвестная ошибка',
    'security_error_check_rpid' => 'Ошибка безопасности<br/>Проверьте ваш переменную окружения WEBAUTHN_ID',
    '2fauth_has_not_a_valid_domain' => 'Домен 2FAuth не является допустимым доменом',
    'user_id_not_between_1_64' => 'Идентификатор пользователя не был от 1 до 64 символов',
    'no_entry_was_of_type_public_key' => 'Запись типа "Публичный ключ" не найдена',
    'unsupported_with_reverseproxy' => 'Не применимо при использовании прокси-аутентификации',
    'user_deletion_failed' => 'Не удалось удалить учетную запись пользователя, данные не были удалены',
    'auth_proxy_failed' => 'Прокси-аунтификация не удалась',
    'auth_proxy_failed_legend' => '2Fauth настроен на работу за прокси-аутентификацией, но ваш прокси не возвращает ожидаемый заголовок. Проверьте настройки и повторите попытку.',
    'invalid_x_migration' => 'Неверные или нечитаемые данные :appname',
    'invalid_2fa_data' => 'Неверные данные 2FA',
    'unsupported_migration' => 'Данные не соответствуют ни одному поддерживаемому формату',
    'unsupported_otp_type' => 'Неподдерживаемый тип OTP',
    'encrypted_migration' => 'Нечитаемо, данные кажутся зашифрованными',
    'no_logo_found_for_x' => 'Нет логотипа для :service',
    'file_upload_failed' => 'Не удалось загрузить файл',
    'unauthorized' => 'Авторизация не выполнена',
    'unauthorized_legend' => 'У вас нет разрешения на просмотр этого ресурса или выполнение этого действия',
    'cannot_delete_the_only_admin' => 'Невозможно удалить единственную учетную запись администратора',
    'error_during_data_fetching' => '💀 Что-то пошло не так во время получения данных',
    'check_failed_try_later' => 'Проверка не удалась, повторите попытку позже',
    'sso_disabled' => 'SSO отключено',
    'sso_bad_provider_setup' => 'Этот SSO провайдер не полностью настроен в вашем .env файле',
    'sso_failed' => 'Аутентификация по SSO отклонена',
    'sso_no_register' => 'Регистрация отключена',
    'sso_email_already_used' => 'Учетная запись с таким же адресом электронной почты уже существует, но не соответствует внешнему идентификатору учетной записи. Не используйте SSO, если вы уже зарегистрированы на 2FAuth с этим email.',
    'account_managed_by_external_provider' => 'Учетная запись управляется внешним провайдером',
    'data_cannot_be_refreshed_from_server' => 'Data cannot be refreshed from server'
];