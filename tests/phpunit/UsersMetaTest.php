<?php

class UsersMetaTest extends FilterTestCase {

    public function test_update_meta_drops_session_keys() {
        $saved = Cooked_Users::update_meta(
            5,
            [
                'profile_photo_id'  => 9,
                'favorites'         => [ 2, 3 ],
                'bio'               => 'chef',
                'id'                => 5,
                'user_nicename'     => 'admin',
                'name'              => 'Admin',
                'profile_photo'     => '<img>',
                'profile_photo_src' => 'http://example.com/a.jpg',
                'raw'               => (object) [ 'user_pass' => 'hash' ],
                'recipes'           => [ 1 ],
            ]
        );

        $expected = [
            'profile_photo_id' => 9,
            'favorites'        => [ 2, 3 ],
            'bio'              => 'chef',
        ];

        $this->assertSame( $expected, $saved );
        $this->assertSame( $expected, get_user_meta( 5, 'cooked_user_meta', true ) );
    }
}
