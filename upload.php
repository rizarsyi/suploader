<?php

// if( strtolower( $_SERVER[ 'REQUEST_METHOD' ] ) == 'post' && !empty( $_FILES ) )
// {
//     foreach( $_FILES[ 'images' ][ 'tmp_name' ] as $index => $tmpName )
//     {
//         if( !empty( $_FILES[ 'images' ][ 'error' ][ $index ] ) )
//         {
//             return false;
//         }

//         $someDestinationPath = "uploads/".$_FILES[ 'images' ][ 'name' ][ $index ];
//         if( !empty( $tmpName ) && is_uploaded_file( $tmpName ) )
//         {
//             move_uploaded_file( $tmpName, $someDestinationPath ); // move to new location perhaps?
//         }
//     }
// }

